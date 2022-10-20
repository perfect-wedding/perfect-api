<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\GiftShopResource;
use App\Models\v1\GiftShop;
use App\Notifications\SendGiftShopInvite;
use Illuminate\Http\Request;

class GiftShopController extends Controller
{
    public function register(Request $request)
    {
        $invite_code = str(base64url_decode($request->get('invited', '')))->explode(':')->last();
        $giftShop = GiftShop::where('invite_code', $invite_code)->first();

        if (!$giftShop) {
            return $this->buildResponse([
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
                'message' => 'Your invitation may have expired.',
            ]);
        }

        $vEmail = !$giftShop->email ? 'required|unique:gift_shops,email,' . $giftShop->id : 'nullable';

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
        $giftShop->socials = $request->socials;
        $giftShop->phone = $request->phone;
        // $giftShop->password = Hash::make($request->password);
        if (!$giftShop->email) {
            $giftShop->email = $request->email;
        }
        $giftShop->invite_code = '';
        $giftShop->slug = (string) GiftShop::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        $giftShop->save();

        $giftShop->notify(new SendGiftShopInvite($giftShop->email, 'accepted'));

        return $this->buildResponse([
            'message' => 'Thank you for accepting this invitation. Subsequently, we will send you emails with all nessesary information and updates to keep you within the loop, thanks!',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ], HttpStatus::CREATED);
    }

    public function show(GiftShop $giftShop)
    {
        return (new GiftShopResource($giftShop))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function invited($token)
    {
        $invite_code = str(base64url_decode($token))->explode(':')->last();
        $giftShop = GiftShop::whereInviteCode($invite_code)->first();

        if (!$giftShop) {
            return $this->buildResponse([
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
                'message' => 'Your invitation may have expired.',
            ]);
        }
        return $this->show($giftShop);
    }
}
