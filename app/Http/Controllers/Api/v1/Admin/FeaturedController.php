<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FeaturedCollection;
use App\Http\Resources\v1\FeaturedResource;
use App\Models\v1\Featured;
use App\Models\v1\Plan;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeaturedController extends Controller
{
    use Meta;

    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        return Validator::make($request->all(), array_merge([
            'type' => ['required', 'string', 'in:company,service,inventory'],
            'type_id' => ['required', 'numeric'],
            'plan_id' => ['nullable', 'numeric', 'exists:plans,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'tenure' => ['nullable', 'string', 'in:monthly,yearly,weekly,daily,hourly'],
            'meta' => ['nullable', 'array'],
            'places' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            'recurring' => ['nullable', 'boolean'],
        ], $rules), $messages, $customAttributes)->validate();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', ['advert.manage']);
        $query = Featured::query();

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
            $query->where('meta->'.$request->meta['key'], $request->meta['value']);
        }

        if ($request->has('places')) {
            $query->place(is_array($request->places) ? $request->places : [$request->places]);
        }

        if ($request->has('type') && in_array($request->type, ['company', 'service', 'inventory'])) {
            $query->whereFeatureableType(str($request->type)->ucfirst()->prepend('App\Models\v1\\')->__toString());
        }

        if ($request->has('status') && in_array($request->status, ['pending', 'approved'])) {
            $query->pending($request->status == 'pending');
        }

        if ($request->paginate === 'none') {
            $ads = $query->get();
        } elseif ($request->paginate === 'cursor') {
            $ads = $query->cursorPaginate($request->get('limit', 15))->withQueryString();
        } else {
            $ads = $query->paginate($request->get('limit', 15))->withQueryString();
        }

        return (new FeaturedCollection($ads))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function show(Request $request, Featured $featured)
    {
        $this->authorize('can-do', ['advert.manage']);

        return (new FeaturedResource($featured))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('can-do', ['advert.manage']);
        $this->validate($request, []);

        $featureable = app('App\Models\v1\\'.ucfirst($request->type))->findOrFail($request->type_id);
        $plan = Plan::where('type', 'featured')->where('meta->type', $request->type)->find($request->plan_id);

        if (! $plan) {
            return $this->buildResponse([
                'message' => __('Your selected plan is not available for :0 items', [$request->type]),
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        // Check if the featureable is already featured
        if ($featureable->featured) {
            return $this->buildResponse([
                'message' => __('This :0 is already featured', [ucfirst($request->type)]),
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ], HttpStatus::BAD_REQUEST);
        }

        $featured = new Featured;

        $featured->featureable_id = $featureable->id;
        $featured->featureable_type = get_class($featureable);
        $featured->plan_id = $plan->id;
        $featured->duration = $request->duration ?? $plan->duration ?? 1;
        $featured->tenure = $request->tenure ?? $plan->tenure ?? 'monthly';
        $featured->meta = $request->meta ?? [];
        $featured->places = $request->places ?? ['marketplace' => true, 'warehouse' => true, 'giftshop' => true];
        $featured->pending = $request->pending ?? false;
        $featured->active = in_array($request->active ?? true, ['true', '1', 1, true], true);
        $featured->recurring = $request->recurring ?? false;
        $featured->save();

        return (new FeaturedResource($featured))->additional([
            'message' => __('":0" is now featured.', [$featureable->title ?? $featureable->name]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Featured $featured)
    {
        $this->authorize('can-do', ['advert.manage']);
        $this->validate($request, []);

        $featureable = $featured->featureable;
        $plan = Plan::where('type', 'featured')->where('meta->type', $request->type)->find($request->plan_id)
             ?? Plan::where('type', 'featured')->where('meta->type', $request->type)->firstOrFail();

        $featured->featureable_id = $featureable->id;
        $featured->featureable_type = get_class($featureable);
        $featured->plan_id = $plan->id ?? $featured->plan_id;
        $featured->duration = $request->duration ?? $featured->duration ?? $plan->duration ?? 1;
        $featured->tenure = $request->tenure ?? $featured->tenure ?? $plan->tenure ?? 'monthly';
        $featured->meta = $request->meta ?? $featured->meta ?? [];
        $featured->places = $request->places ?? $featured->places ?? ['marketplace' => true, 'warehouse' => true, 'giftshop' => true];
        $featured->active = in_array($request->active ?? $featured->active ?? true, ['true', '1', 1, true], true);
        $featured->pending = $request->pending ?? false;
        $featured->recurring = $request->recurring ?? false;

        return (new FeaturedResource($featured))->additional([
            'message' => __('Featuring for ":0" has been updated.', [$featureable->title ?? $featureable->name]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Manage the visibility of the item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\v1\Featured  $featured
     * @return \Illuminate\Http\Response
     */
    public function visibility(Request $request, Featured $featured)
    {
        $this->authorize('can-do', ['advert.manage']);
        Validator::validate($request->all(), [
            'active' => ['required_without:pending', 'boolean'],
            'pending' => ['required_without:active', 'boolean'],
        ]);

        $featureable = $featured->featureable;

        $featured->active = $request->active == true;
        $featured->pending = $request->pending == true;
        $featured->save();

        $pending = $request->pending == false ? __('approved') : __('set to pending');
        $active = $request->active == true ? __('activated') : __('deactivated');
        $done = $request->has('pending') && $request->has('active')
            ? ($active.' and '.$pending)
            : ($request->has('pending') ? $pending : $active);

        return (new FeaturedResource($featured))->additional([
            'message' => __('Featuring for ":0" has been :1.', [$featureable->title ?? $featureable->name, $done]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['advert.manage']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = Featured::whereId($item)->first();
                if ($item) {
                    $delete = $item->delete();

                    return count($request->items) === 1 ? ($item->featureable->name ?? $item->featureable->title) : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $count->count() === 1
                    ? __(':0 has been deleted', [$count->first()])
                    : __(':0 items have been deleted.', [$count->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Featured::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->featureable->name ?? $item->featureable->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
