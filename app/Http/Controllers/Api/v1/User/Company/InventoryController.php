<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\InventoryCollection;
use App\Http\Resources\v1\Business\InventoryResource;
use App\Http\Resources\v1\ReviewCollection;
use App\Models\v1\Company;
use App\Models\v1\Image;
use App\Models\v1\Inventory;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ToneflixCode\LaravelFileable\Media;

class InventoryController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @param  string|null  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Company $company, $type = null)
    {
        $limit = $request->get('limit', 15);
        $query = $company->inventories();
        if ($type === 'top') {
            $query = $query->orderByDesc(function ($q) {
                $q->select([DB::raw('count(reviews.id) oc from reviews')])
                    ->where([['reviewable_type', Inventory::class], ['reviewable_id', DB::raw('services.id')]]);
            })->orderByDesc(function ($q) {
                $q->select([DB::raw('count(orders.id) oc from orders')])
                    ->where([['orderable_type', Inventory::class], ['orderable_id', DB::raw('services.id')]]);
            });
        } elseif ($type === 'most-ordered') {
            $query = $query->orderByDesc(function ($q) {
                $q->select([DB::raw('count(orders.id) oc from orders')])
                    ->where([['orderable_type', Inventory::class], ['orderable_id', DB::raw('services.id')]]);
            });
        } elseif ($type === 'top-reviewed') {
            $query = $query->orderByDesc(function ($q) {
                $q->select([DB::raw('count(reviews.id) oc from reviews')])
                    ->where([['reviewable_type', Inventory::class], ['reviewable_id', DB::raw('services.id')]]);
            });
        } else {
        }

        $inventories = $query->paginate($limit)->withQueryString();

        return (new InventoryCollection($inventories))->additional([
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
    public function store(Request $request, Company $company)
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'category_id' => ['required', 'numeric'],
            'price' => ['required', 'numeric', 'min:1'],
            'stock' => ['required', 'numeric', 'min:1'],
            'basic_info' => ['required', 'string', 'min:3', 'max:55'],
            'details' => ['required', 'string', 'min:3', 'max:550'],
            'colors' => ['nullable', 'array', 'max:12'],
        ], [
            'category_id.required' => 'Please select a category.',
        ]);

        $inventory = new Inventory();
        $inventory->user_id = Auth::id();
        $inventory->company_id = $company->id;
        $inventory->category_id = $request->category_id;
        $inventory->name = $request->name;
        $inventory->type = 'warehouse';
        $inventory->price = $request->price;
        $inventory->stock = $request->stock;
        $inventory->colors = $request->colors;
        $inventory->basic_info = $request->basic_info;
        $inventory->details = $request->details;
        $inventory->code = str($company->name)->limit(2, '')->prepend(str('WH')->append($this->generate_string(6, 3)))->upper();
        $inventory->save();

        if ($request->hasFile('images') && is_array($request->file('images'))) {
            foreach ($request->file('images') as $key => $image) {
                $inventory->images()->save(new Image([
                    'file' => (new Media)->save('default', 'images', null, $key),
                ]));
            }
        }

        return (new InventoryResource($inventory))->additional([
            'message' => "{$inventory->name} has been created successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company, $id)
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'category_id' => ['required', 'numeric'],
            'price' => ['required', 'numeric', 'min:1'],
            'stock' => ['required', 'numeric', 'min:1'],
            'basic_info' => ['required', 'string', 'min:3', 'max:55'],
            'details' => ['required', 'string', 'min:3', 'max:550'],
            'colors' => ['nullable', 'array', 'max:12'],
        ], [
            'category_id.required' => 'Please select a category.',
        ]);

        $inventory = $company->inventories()->findOrFail($id);
        $inventory->category_id = $request->category_id ?? $inventory->category_id;
        $inventory->name = $request->name ?? $inventory->name;
        $inventory->price = $request->price ?? $inventory->price;
        $inventory->stock = $request->stock ?? $inventory->stock;
        $inventory->colors = $request->colors ?? $inventory->colors;
        $inventory->basic_info = $request->basic_info ?? $inventory->basic_info;
        $inventory->details = $request->details ?? $inventory->details;
        $inventory->save();

        // if ($request->hasFile('images') && is_array($request->file('images'))) {
        //     foreach ($request->file('images') as $key => $image) {
        //         $image = Image::findOrNew($image);
        //         $image->imageable_id = $inventory->id;
        //         $image->imageable_type = Inventory::class;
        //         $image->file = (new Media)->save('default', 'images', $image->file, $key);
        //         $image->save();
        //     }
        // }

        if ($request->hasFile('images') && is_array($request->file('images'))) {
            foreach ($request->file('images') as $key => $image) {
                $inventory->images()->save(new Image([
                    'file' => (new Media)->save('default', 'images', null, $key),
                ]));
            }
        }

        return (new InventoryResource($inventory))->additional([
            'message' => "{$inventory->name} has been updated successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Display the services.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reviews(Inventory $inventory)
    {
        return (new ReviewCollection($inventory->reviews()->with('user')->paginate()))->additional([
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
    public function show(Request $request, $company, Inventory $inventory)
    {
        return (new InventoryResource($inventory))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = Inventory::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} items have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Inventory::findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "\"{$item->name}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}