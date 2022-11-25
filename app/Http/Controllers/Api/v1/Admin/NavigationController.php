<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\NavigationCollection;
use App\Http\Resources\v1\Home\NavigationResource;
use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Home\Homepage;
use App\Models\v1\Inventory;
use App\Models\v1\Navigation;
use App\Models\v1\Service;
use App\Models\v1\ShopItem;
use App\Traits\Meta;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    use Meta;

    public function navigable(Request $request)
    {
        // Navigables includes [Homepage, Company, Service, Inventory, ShopItem, Category]
        if ($request->type == 'Homepage' || $request->type == Homepage::class) {
            $navigable = Homepage::find($request->navigable_id);
        } elseif ($request->type == 'Category' || $request->type == Category::class) {
            $navigable = Category::find($request->navigable_id);
        } elseif ($request->type == 'Company' || $request->type == Company::class) {
            $navigable = Company::find($request->navigable_id);
        } elseif ($request->type == 'Service' || $request->type == Service::class) {
            $navigable = Service::find($request->navigable_id);
        } elseif ($request->type == 'Inventory' || $request->type == Inventory::class) {
            $navigable = Inventory::find($request->navigable_id);
        } elseif ($request->type == 'GiftItem' || $request->type == ShopItem::class) {
            $navigable = ShopItem::find($request->navigable_id);
        } else {
            return response()->json([
                'message' => HttpStatus::message(HttpStatus::BAD_REQUEST),
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ], HttpStatus::BAD_REQUEST);
        }

        return $navigable;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', ['website']);
        $query = Navigation::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('group', 'like', "%$request->search%")
                    ->orWhere('location', 'like', "%$request->search%")
                    ->orWhereHas('navigable', function ($q) use ($request) {
                        $q->where('title', 'like', "%$request->search%");
                    });
            });
        }

        if ($request->group && $request->group !== 'all') {
            $query->byGroup($request->group);
        }

        if ($request->location) {
            $query->byLocation($request->location);
        }

        if ($request->status && $request->status != 'all' && in_array($request->status, ['active', 'inactive'])) {
            $query->{$request->status}($request->location);
        }

        // Reorder Columns
        if ($request->order && $request->order === 'latest') {
            $query->latest();
        } elseif ($request->order && $request->order === 'oldest') {
            $query->oldest();
        } elseif ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        if ($request->group === 'all') {
            // Split the collection into groups by location the split the groups by group and return the collection
            $navigations = $query->get()->groupBy('location')->map(function ($item, $key) {
                return $item->groupBy('group')->map(function ($item, $key) {
                    return new NavigationCollection($item);
                });
            });

            return $this->buildResponse([
                'message' => HttpStatus::message(HttpStatus::OK),
                'status' => 'success',
                'status_code' => HttpStatus::OK,
                'data' => $navigations,
            ]);
        }

        $navs = $query->paginate(15)->onEachSide(1)->withQueryString();

        return (new NavigationCollection($navs))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'group' => ['required', 'string', 'min:1'],
            'location' => ['required', 'string', 'min:3'],
            'type' => ['required', 'string', 'in:Homepage,Company,Service,Inventory,ShopItem,Category'],
            'navigable_id' => ['required', 'integer'],
            'important' => ['nullable', 'string', 'in:yes,no,true,false,1,0'],
            'active' => ['nullable', 'string', 'in:yes,no,true,false,1,0'],
        ]);

        $navigable = $this->navigable($request);

        if (Navigation::where('navigable_id', $request->navigable_id)->where('navigable_type', get_class($navigable))->where('group', $request->group)->where('location', $request->location)->exists()) {
            return $this->buildResponse([
                'message' => 'Navigation already exists',
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ], HttpStatus::UNPROCESSABLE_ENTITY);
        }

        $navigation = $navigable->navigations()->create([
            'title' => $request->title ?? null,
            'group' => $request->group,
            'location' => $request->location,
            'active' => in_array($request->active, ['yes', 'true', '1']) ? true : false,
            'important' => in_array($request->important, ['yes', 'true', '1']) ? true : false,
        ]);

        return (new NavigationResource($navigation))->additional([
            'message' => __('Navigation created successfully'),
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\v1\Navigation  $navigation
     * @return \Illuminate\Http\Response
     */
    public function show(Navigation $navigation)
    {
        $this->authorize('can-do', ['website']);

        return (new NavigationResource($navigation))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Navigation  $navigation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Navigation $navigation)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'group' => ['required', 'string', 'min:1'],
            'location' => ['required', 'string', 'min:3'],
            'type' => ['required', 'string', 'in:Homepage,Company,Service,Inventory,ShopItem,Category'],
            'navigable_id' => ['nullable', 'integer'],
            'important' => ['nullable', 'string', 'in:yes,no,true,false,1,0'],
            'active' => ['nullable', 'string', 'in:yes,no,true,false,1,0'],
        ]);

        $navigable = $this->navigable($request);
        $navigation->update([
            'title' => $request->title ?? null,
            'group' => $request->group,
            'location' => $request->location,
            'active' => in_array($request->active, ['yes', 'true', '1']) ? true : false,
            'important' => in_array($request->important, ['yes', 'true', '1']) ? true : false,
            'navigable_id' => $navigable->id,
            'navigable_type' => get_class($navigable),
        ]);

        return (new NavigationResource($navigation))->additional([
            'message' => __('Navigation updated successfully'),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    public function reorder(Request $request, $id)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'data' => ['required', 'array'],
            'data.*.id' => ['required', 'integer'],
            'data.*.priority' => ['required', 'integer'],
        ], [
            'data.*.id.required' => 'The id field is required for each navigation item',
            'data.*.priority.required' => 'The priority field is required for each navigation item',
        ]);

        $navigation = null;

        // Loop through the data and update the priority
        foreach ($request->data as $item) {
            $navigation = Navigation::find($item['id']);
            $navigation && $navigation->update([
                'priority' => $item['priority'],
            ]);
        }

        return (new NavigationResource($navigation))->additional([
            'message' => __("Navigation :0 has been updated successfully", [
                str('priority')->plural(count($request->data)),
            ]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('can-do', ['website']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = Navigation::whereId($item)->first();
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
            $item = Navigation::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}