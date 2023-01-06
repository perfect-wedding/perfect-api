<?php

namespace App\Http\Controllers\Api\v1\Provider;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\ServiceCollection;
use App\Http\Resources\v1\Business\ServiceResource;
use App\Http\Resources\v1\ReviewCollection;
use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use App\Models\v1\ShopItem;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
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
        $cats = explode(',', $request->get('categories', ''));
        if ($request->has('categories') && ! empty($cats[0])) {
            $query = Service::whereHas('category', function ($category) use ($cats) {
                $category->whereIn('slug', $cats)
                        ->orWhereIn('id', $cats)
                        ->ownerVerified('services');
            });
        } elseif ($request->has('category')) {
            $category = Category::where('slug', $request->get('category'))
                                ->orWhere('id', $request->get('category'))
                                ->ownerVerified('market')->firstOrFail();
            $query = $category->services();
        } else {
            $query = Service::query();
        }

        if ($request->has('price_range')) {
            $query->whereBetween('price', rangeable($request->input('price_range')));
        }

        if ($request->has('ratings')) {
            // Filter Items by their average review ratings
            $query->whereHas('reviews', function ($review) use ($request) {
                $review->selectRaw('avg(rating) as average_rating')
                        ->groupBy('reviewable_id')
                        ->havingRaw('avg(rating) >= ?', [$request->input('ratings')]);
            });
        }

        if ($request->has('company')) {
            $company = Company::where('slug', $request->get('company'))
                                ->orWhere('id', $request->get('company'))
                                ->ownerVerified()->firstOrFail();
            $query->where('company_id', $company->id);
        }

        $services = $query->ownerVerified()
                        ->orderingBy()
                        ->paginate($request->input('limit', 15))
                        ->withQueryString()
                        ->onEachSide(1);

        return (new ServiceCollection($services))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the services.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @param  string|null  $type
     * @return \Illuminate\Http\Response
     */
    public function companyIndex(Request $request, Company $company, $type = null)
    {
        $limit = $request->input('limit', 15);
        $type = $request->input('type', $type);
        $query = $company->services();

        if ($type && in_array($type, ['top', 'most-ordered', 'most-reviewed'])) {
            $services = $query->orderingBy($type)->paginate($limit);
        } else {
            $services = $query->paginate($limit);
        }

        return (new ServiceCollection($services))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the services.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reviews(Service $service)
    {
        return (new ReviewCollection($service->reviews()->with('user')->paginate()))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Service $service)
    {
        return (new ServiceResource($service))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Process Checkout.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function checkout(Request $request)
    {
        $reference = config('settings.trx_prefix', 'TRX-').$this->generate_string(20, 3);
        $user = Auth::user();

        $items = collect($request->items)->map(function ($item) use ($user) {
            $quantity = $item['quantity'] ?? 1;
            if ($item['type'] === 'inventory' || $item['type'] === 'giftshop') {
                $query = $item['type'] === 'inventory'
                    ? Inventory::query()
                    : ShopItem::query();

                $orderable = $query->find($item['item_id']);

                $package = ['id' => 0];
                $requested = $orderable;
                $total = $orderable->price * $quantity;
            } else {
                $requested = $user->orderRequests()->find($item['request_id'] ?? '');
                if (! $requested) {
                    return null;
                }
                $orderable = $requested->orderable;
                $package = $orderable->offers()->find($item['package_id']) ?? ['id' => 0];
                $total = $orderable->offerCalculator($item['package_id']) * $quantity;
            }
            $transaction = $orderable->transactions();
            $item['due'] = $orderable->price;

            return [
                'package' => $package,
                'quantity' => $quantity,
                'color' => $item['color'] ?? null,
                'due_date' => $item['due_date'] ?? null,
                'location' => $item['location'] ?? null,
                'destination' => $item['destination'] ?? null,
                'orderable' => $orderable,
                'transaction' => $transaction,
                'request' => $requested,
                'total' => $total,
            ];
        })->filter(fn ($item) => $item !== null);

        if ($request->method === 'wallet') {
            if ($user->wallet_bal >= $items->sum('total')) {
                $items->map(function ($item) use ($reference) {
                    $quantity = $item['quantity'];
                    $orderable = $item['orderable'];
                    $price = $orderable->price;
                    $type = $orderable instanceof Service ? 'service' : 'inventory';
                    $item['transaction']->create([
                        'reference' => $reference,
                        'user_id' => Auth::id(),
                        'amount' => $item['total'],
                        'method' => 'Wallet',
                        'status' => 'pending',
                        'due' => $item['total'],
                        'offer_charge' => $item['package']['id'] ? $orderable->packAmount($item['package']['id']) : $price,
                        'discount' => $item['package']['id'] ? (
                            $item['package']->type === 'discount'
                                ? (($dis = $orderable->price - $item['total']) > 0
                                ? $dis : 0.00) : 0.00
                        ) : 0.00,
                        'data' => [
                            'due_date' => $item['due_date'] ?? '',
                            'location' => $item['location'] ?? '',
                            'request_id' => $item['request']['id'] ?? '',
                            'destination' => $item['destination'] ?? '',
                            ($type === 'service'
                                ? 'service_id'
                                : 'item_id') => $item['orderable']['id'] ?? '',
                            ($type === 'service'
                                ? 'service_title'
                                : 'item_name') => $orderable->title,
                            'price' => $price,
                            'quantity' => $quantity,
                        ],
                    ]);
                });

                $detail = trans_choice('Payment for order of :0 service', $items->count(), [$items->count()]);
                $user->useWallet('Service Orders', 0 - $items->sum('total'), $detail, null, 'complete', $reference);
            } else {
                return $this->buildResponse([
                    'message' => 'You do not have enough funds in your wallet',
                    'status' => 'error',
                    'status_code' => HttpStatus::BAD_REQUEST,
                ], HttpStatus::BAD_REQUEST);
            }

            return $this->buildResponse([
                'reference' => $reference,
                'message' => __('Transaction initiated successfully'),
                'status' => 'success',
                'status_code' => HttpStatus::CREATED,
            ]);
        }

        return $this->buildResponse([
            'data' => $items,
            'message' => __('Transaction failed'),
            'status' => 'success',
            // 'refresh' => ['user' => new UserResource(Auth::user())],
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Review the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function review(Request $request, $id)
    {
        //
    }
}