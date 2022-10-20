<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\GiftShopItemCollection;
use App\Http\Resources\v1\Business\GiftShopItemResource;
use App\Http\Resources\v1\Business\GiftShopResource;
use App\Models\v1\GiftShop;
use App\Traits\Meta;
use Illuminate\Http\Request;
use App\Http\Resources\v1\ReviewCollection;
use App\Models\v1\Image;
use App\Models\v1\ShopItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ToneflixCode\LaravelFileable\Media;

class GiftShopStore extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Http\Controllers\Api\v1\Admin\GiftShop $giftshop
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, GiftShop $giftshop)
    {
        \Gate::authorize('can-do', ['company.manage']);
        $query = $giftshop->items();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                      ->orWhere('code', $request->search)
                      ->orWhere('basic_info', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        $items = $query->paginate(15)->onEachSide(1)->withQueryString();

        return (new GiftShopItemCollection($items))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'shop' => new GiftShopResource($giftshop),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Http\Controllers\Api\v1\Admin\GiftShop $giftshop
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, GiftShop $giftshop)
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

        $item = new ShopItem;
        $item->user_id = Auth::id();
        $item->gift_shop_id = $giftshop->id;
        $item->category_id = $request->category_id;
        $item->name = $request->name;
        $item->price = $request->price;
        $item->stock = $request->stock;
        $item->colors = $request->colors;
        $item->basic_info = $request->basic_info;
        $item->details = $request->details;
        $item->code = str($giftshop->name)->limit(2, '')->prepend(str('GS')->append($this->generate_string(6, 3)))->upper();
        $item->save();

        if ($request->hasFile('images') && is_array($request->file('images'))) {
            foreach ($request->file('images') as $key => $image) {
                $image = Image::findOrNew($image);
                $image->imageable_id = $item->id;
                $image->imageable_type = ShopItem::class;
                $image->file = $image;
                $image->save();
            }
        }

        return (new GiftShopItemResource($item))->additional([
            'message' => "{$item->name} has been created successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, GiftShop $giftshop, $item)
    {
        \Gate::authorize('can-do', ['company.manage']);

        $item = $giftshop->items()->where('id', $item)->orWhere('slug', $item)->firstOrFail();

        return (new GiftShopItemResource($item))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GiftShop $giftshop, $item)
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

        $item = $giftshop->items()->where('id', $item)->orWhere('slug', $item)->firstOrFail();

        $item->name = $request->name ?? $item->name;
        $item->price = $request->price ?? $item->price;
        $item->stock = $request->stock ?? $item->stock;
        $item->colors = $request->colors ?? $item->colors;
        $item->basic_info = $request->basic_info ?? $item->basic_info;
        $item->details = $request->details ?? $item->details;
        $item->save();

        if ($request->hasFile('images') && is_array($request->file('images'))) {
            foreach ($request->file('images') as $key => $image) {
                $image = Image::findOrNew($image);
                $image->imageable_id = $item->id;
                $image->imageable_type = ShopItem::class;
                $image->file = (new Media)->save('default', 'images', $image->file, $key);
                $image->save();
            }
        }

        return (new GiftShopItemResource($item))->additional([
            'message' => "{$item->name} has been updated successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}