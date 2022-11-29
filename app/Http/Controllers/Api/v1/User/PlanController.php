<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FeaturedResource;
use App\Http\Resources\v1\PlanCollection;
use App\Http\Resources\v1\PlanResource;
use App\Models\v1\Featured;
use App\Models\v1\Plan;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yabacon\Paystack;

class PlanController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Plan::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', "%$request->search%");
                $query->orWhere('price', $request->search);
                $query->orWhere('tenure', $request->search);
                $query->orWhere('duration', '>=', $request->search);
            });
        }

        // Reorder Columns
        if ($request->has('order') && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        if ($request->has('meta') && isset($request->meta['key']) && isset($request->meta['value'])) {
            $query->where('meta->' . $request->meta['key'], $request->meta['value']);
        }

        if ($request->has('places')) {
            $query->place(is_array($request->places) ? $request->places : [$request->places]);
        }

        if ($request->has('type')) {
            $query->whereType($request->type ?? NULL);
        }

        if ($request->paginate === 'none') {
            $ads = $query->get();
        } elseif ($request->paginate === 'cursor') {
            $ads = $query->cursorPaginate($request->get('limit', 15))->withQueryString();
        } else {
            $ads = $query->paginate($request->get('limit', 15))->withQueryString();
        }

        return (new PlanCollection($ads))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function show(Request $request, Plan $plan)
    {
        return (new PlanResource($plan))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Subscribe to a plan
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request, Plan $plan, $action = 'create')
    {
        $this->validate($request, [
            'type_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:255'],
            'recurring' => ['nullable', 'in:true,false,1,0'],
            'tenure' => ['nullable', 'string', 'in:hourly,daily,weekly,monthly,yearly'],
        ]);

        $msg = null;
        $item = null;
        $error = null;
        $resource = null;
        if ($plan->type === 'featured') {
            $item = app('App\Models\v1\\' . ucfirst($request->type))->findOrFail($request->type_id);

            if ($plan->meta['type'] != $request->type) {
                $error = __('Your selected plan is not available for :0 items', [$request->type]);
            }

            // Check if the featureable is currently featured
            if ($item->featured) {
                $error = __('This :0 is already featured', [$request->type]);
            }
        }

        if ($error) {
            return $this->buildResponse([
                'message' => $error,
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        $user = Auth::user();
        $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
        $transactions = $plan->transactions()->whereUserId($user->id);

        if ($action === 'create') {
            $reference = config('settings.trx_prefix', 'TRX-') . $this->generate_string(20, 3);
            $due = $plan->price;
            $real_due = round($due * 100, 2);
            $transaction = $transactions->create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $due,
                'type' => 'plan_subscription',
                'status' => 'pending',
                'description' => 'Subscription to ' . $plan->title,
                'method' => 'Paystack',
                'restricted' => true,
            ]);

            // Dont initialize paystack for inline transaction
            if ($request->inline) {
                $tranx = [
                    'data' => ['reference' => $reference],
                ];
                $real_due = $due;
            } else {
                try {
                    $tranx = $paystack->transaction->initialize([
                        'amount' => $real_due,       // in kobo
                        'email' => $user->email,     // unique to customers
                        'reference' => $reference,   // unique to transactions
                        'callback_url' => $request->get('redirect'),
                    ]);
                } catch (ApiException $e) {
                    return $this->buildResponse([
                        'message' => $e->getMessage(),
                        'status' => 'error',
                        'status_code' => HttpStatus::BAD_REQUEST,
                        'payload' => $e->getResponseObject(),
                    ]);
                }
            }
        } elseif ($action === 'verify') {
            $tranx = $paystack->transaction->verify([
                'reference' => $request->reference,
            ]);

            if ('success' === $tranx->data->status) {
                $transaction = $transactions->where('reference', $request->reference)->where('status', 'pending')->firstOrFail();

                if ($plan->type === 'featured') {
                    $response = $this->featureItem($item, $plan, $request->tenure, $request->recurring);
                    $resource = $response['resource'];
                    $msg = $response['message'];

                }

                $transaction->status = 'completed';
                $transaction->save();
            }
        }

        $additional = [
            'message' => $msg ?? HttpStatus::message($action === 'create' ? HttpStatus::CREATED : HttpStatus::ACCEPTED),
            'status' => 'success',
            'payload' => $tranx ?? new \stdClass(),
            'status_code' => $action === 'create' ? HttpStatus::CREATED : HttpStatus::ACCEPTED,
            'transaction' => $transaction ?? new \stdClass(),
            'amount' => $real_due ?? 0,
        ];

        return $resource
        ?   $resource->additional($additional)
                ->response()
                ->setStatusCode($action === 'create' ? HttpStatus::CREATED : HttpStatus::ACCEPTED)
        :  $this->buildResponse($additional);
    }

    protected function featureItem($item, $plan, $tenure = null, $recurring = false)
    {
        $tenure = $tenure ? $plan->split : $plan;

        $featured = Featured::firstOrNew([
            'featureable_id' => $item->id,
            'featureable_type' => get_class($item),
        ]);

        $featured->plan_id = $plan->id;
        $featured->duration = $tenure['duration'] ?? 1;
        $featured->tenure = $tenure['tenure'] ?? 'monthly';
        $featured->meta = [];
        $featured->places = $plan->meta['places'] ?? ['marketplace' => true, 'warehouse' => true, 'giftshop' => true];
        $featured->pending = true;
        $featured->active = false;
        $featured->recurring = $recurring ?? false;
        $featured->save();

        return [
            'resource' => new FeaturedResource($featured),
            'message' => __('":0" is now queued to be featured, we will review this request and get back to you if necessary',
                [$item->title ?? $item->name]
            ),
        ];
    }
}
