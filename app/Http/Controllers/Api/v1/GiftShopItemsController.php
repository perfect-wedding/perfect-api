<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\GiftShopCollection;
use App\Http\Resources\v1\Business\GiftShopItemCollection;
use App\Http\Resources\v1\Business\GiftShopResource;
use App\Models\v1\Category;
use App\Models\v1\GiftShop;
use App\Models\v1\ShopItem;
use App\Notifications\SendGiftShopInvite;
use Illuminate\Http\Request;

class GiftShopItemsController extends Controller
{
    /**
     * Display a listing of all inventory items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cats = explode(',', $request->get('categories', ''));
        if ($request->has('categories') && ! empty($cats[0])) {
            $query = ShopItem::whereHas('category', function ($category) use ($cats) {
                $category->whereIn('slug', $cats)
                        ->orWhereIn('id', $cats)
                        ->ownerVerified('giftshop');
            });
        } elseif ($request->has('category')) {
            $category = Category::where('slug', $request->get('category'))
                                ->orWhere('id', $request->get('category'))
                                ->ownerVerified('giftshop')->firstOrFail();
            $query = $category->shop_items();
        } else {
            $query = ShopItem::query();
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
            $company = GiftShop::where('slug', $request->get('company'))
                                ->orWhere('id', $request->get('company'))
                                ->active()->firstOrFail();
            $query->where('gift_shop_id', $company->id);
        }

        $items = $query->shopActive()->orderingBy()->paginate($request->input('limit', 15))->withQueryString()->onEachSide(1);

        return (new GiftShopItemCollection($items))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function register(Request $request)
    {
        $invite_code = str(base64url_decode($request->get('invited', '')))->explode(':')->last();
        $giftShop = GiftShop::where('invite_code', $invite_code)->first();

        if (! $giftShop) {
            return $this->buildResponse([
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
                'message' => 'Your invitation may have expired.',
            ]);
        }

        $vEmail = ! $giftShop->email ? 'required|unique:gift_shops,email,'.$giftShop->id : 'nullable';

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'socials' => 'nullable|array',
            'phone' => 'required|string',
            'image' => 'nullable|image|max:1024|mimetypes:image/jpeg,image/png,image/jpeg',
            // 'password' => 'required|string|min:6',
            'email' => $vEmail.'|email',
        ]);

        $slug = str($request->name)->slug();

        $giftShop->name = $request->name;
        $giftShop->description = $request->description;
        $giftShop->socials = collect($request->socials)->filter(fn ($social) => ! empty($social))->toArray();
        $giftShop->phone = $request->phone;
        // $giftShop->password = Hash::make($request->password);
        if (! $giftShop->email) {
            $giftShop->email = $request->email;
        }
        $giftShop->invite_code = '';
        $giftShop->slug = (string) GiftShop::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        $giftShop->active = true;
        $giftShop->save();

        $giftShop->notify(new SendGiftShopInvite($giftShop->email, 'accepted'));

        return $this->buildResponse([
            'message' => 'Thank you for accepting this invitation. Subsequently, we will send you emails with all nessesary information and updates to keep you within the loop, thanks!',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ], HttpStatus::CREATED);
    }

    public function show(GiftShop $giftshop)
    {
        return (new GiftShopResource($giftshop))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the inventory based on the category selected.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Category  $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function category(Request $request, Category $category)
    {
        $limit = $request->get('limit', 15);

        $query = $category->shop_items()->shopActive();

        if ($request->paginate === 'cursor') {
            $giftshops = $query->cursorPaginate($limit);
        } else {
            $giftshops = $query->paginate($limit);
        }

        $response = new GiftShopItemCollection($giftshops);

        return $response->additional([
            'category' => $category,
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function invited($token)
    {
        $invite_code = str(base64url_decode($token))->explode(':')->last();
        $giftShop = GiftShop::whereInviteCode($invite_code)->first();

        if (! $giftShop) {
            return $this->buildResponse([
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
                'message' => 'Your invitation may have expired.',
            ]);
        }

        return $this->show($giftShop);
    }
}