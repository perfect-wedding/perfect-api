<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PlanCollection;
use App\Http\Resources\v1\PlanResource;
use App\Models\v1\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        return Validator::make($request->all(), array_merge([
            'title' => ['required', 'string', 'max:55'],
            'basic_info' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'features' => ['nullable', 'array'],
            'popular' => ['nullable', 'in:true,false,0,1'],
            'icon' => ['nullable', 'string', 'max:255'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'duration' => ['required', 'integer', 'min:1'],
            'tenure' => ['required', 'string', 'max:255', 'in:monthly,yearly,weekly,daily,hourly'],
            'type' => ['nullable', 'string', 'max:255', 'in:free,featured'],
            'meta' => ['nullable', 'array'],
            'places' => ['nullable', 'array'],
            'split' => ['nullable', 'array'],
            'annual' => ['nullable', 'in:true,false,0,1'],
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
        $this->authorize('can-do', ['plan.manage']);
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
            $query->where('meta->'.$request->meta['key'], $request->meta['value']);
        }

        if ($request->has('places')) {
            $query->place(is_array($request->places) ? $request->places : [$request->places]);
        }

        if ($request->has('type')) {
            $query->whereType($request->type ?? null);
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
        $this->authorize('can-do', ['plan.manage']);

        return (new PlanResource($plan))->additional([
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
        $this->authorize('can-do', ['plan.manage']);
        $this->validate($request, []);

        $plan = new Plan;
        $plan->title = $request->title;
        $plan->basic_info = $request->basic_info;
        $plan->price = $request->price;
        $plan->features = $request->features;
        $plan->popular = in_array($request->popular, ['true', '1', 1, true], true);
        $plan->icon = $request->icon;
        $plan->trial_days = $request->trial_days;
        $plan->duration = $request->duration;
        $plan->tenure = $request->tenure;
        $plan->type = $request->type;
        $plan->annual = in_array($request->annual, ['true', '1', 1, true], true);
        $plan->meta = $request->meta;
        $plan->meta = $plan->meta->merge(['places' => $request->meta->places ?? $request->places ?? ['all']]);

        $plan->save();

        return (new PlanResource($plan))->additional([
            'message' => __('New plan ":0" has been created.', [$plan->title]),
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
    public function update(Request $request, Plan $plan)
    {
        $this->authorize('can-do', ['plan.manage']);
        $this->validate($request, []);

        $plan = $plan;
        $plan->title = $request->title;
        $plan->basic_info = $request->basic_info;
        $plan->price = $request->price;
        $plan->features = $request->features;
        $plan->popular = in_array($request->popular, ['true', '1', 1, true], true);
        $plan->icon = $request->icon;
        $plan->trial_days = $request->trial_days;
        $plan->duration = $request->duration;
        $plan->tenure = $request->tenure;
        $plan->type = $request->type;
        $plan->split = $request->split;
        $plan->annual = in_array($request->annual, ['true', '1', 1, true], true);
        $plan->meta = $request->meta;
        $plan->meta = $plan->meta->merge(['places' => $request->meta->places ?? $request->places ?? ['all']]);

        $plan->save();

        return (new PlanResource($plan))->additional([
            'message' => __(':0 has been updated.', [$plan->title]),
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
        $this->authorize('can-do', ['plan.manage']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = Plan::whereId($item)->first();
                if ($item) {
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->title : $delete;
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
            $item = Plan::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
