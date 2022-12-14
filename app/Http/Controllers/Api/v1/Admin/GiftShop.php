<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\GiftShopCollection;
use App\Http\Resources\v1\Business\GiftShopResource;
use App\Models\v1\GiftShop as GiftShopModel;
use App\Notifications\SendGiftShopInvite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GiftShop extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', ['giftshop']);
        $query = GiftShopModel::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                      ->orWhere('description', 'like', "%$request->search%")
                      ->orWhere('merchant_name', 'like', "%$request->search%");
            });
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

        $shops = $query->paginate(15)->onEachSide(1)->withQueryString();

        return (new GiftShopCollection($shops))->additional([
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Http\Controllers\Api\v1\Admin\GiftShop  $giftshop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GiftShopModel $giftshop)
    {
        $this->authorize('can-do', ['giftshop']);
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'socials' => 'nullable|array',
            'phone' => 'required|string',
            'image' => 'nullable|image|max:1024|mimetypes:image/jpeg,image/png,image/jpeg',
            // 'password' => 'required|string|min:6',
            'email' => ['email', 'required', Rule::unique('gift_shops', 'email')->ignore($giftshop->id, 'id')],
        ]);

        $giftshop->name = $request->name;
        $giftshop->description = $request->description;
        $giftshop->socials = $request->socials;
        $giftshop->phone = $request->phone;
        // $giftshop->password = Hash::make($request->password);
        $giftshop->email = $request->email;
        $giftshop->save();

        return (new GiftShopResource($giftshop))->additional([
            'message' => __(':0 has been updated successfully.', [$giftshop->name]),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Send an invitation to the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendInvitation(Request $request)
    {
        $this->authorize('can-do', ['giftshop']);
        $this->validate($request, [
            'email' => 'nullable|email|unique:gift_shops,email',
            'merchant_name' => 'required|string',
            'link' => 'required|string',
        ], [
            'email.unique' => 'A gift shop with this email already exists.',
        ]);

        $giftshop = GiftShopModel::create([
            'email' => $request->email,
            'name' => $request->merchant_name,
            'merchant_name' => $request->merchant_name,
        ]);

        $link = $request->input('link').'?invited='.base64url_encode(md5($giftshop->email).':'.$giftshop->invite_code);

        if ($giftshop->email) {
            $giftshop->notify(new SendGiftShopInvite($link));
        }

        $message = ! $request->email
            ? __('An invitation link has been generated for :0, as you did not provide an email address you would have to copy the link and give to them in person!', [$request->merchant_name])
            : __('An invitation has been sent to :0!', [$request->merchant_name]);

        return (new GiftShopResource($giftshop))->additional([
            'message' => $message,
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
            'link' => $link,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Manually verify the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function manualVerify(Request $request)
    {
        $this->authorize('can-do', ['giftshop']);
        $this->validate($request, [
            'shop_id' => 'required|integer|exists:gift_shops,id',
        ]);

        $giftshop = GiftShopModel::findOrFail($request->shop_id);

        $error = null;

        if ($giftshop->active) {
            $error = __(':0 is already verified!', [$giftshop->name]);
        } elseif (! $giftshop->email) {
            $error = __(':0 can not be verified, please add an email address and try again!', [$giftshop->name]);
        }

        if ($error) {
            return $this->buildResponse([
                'message' => $error,
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        $giftshop->active = true;
        $giftshop->save();

        return (new GiftShopResource($giftshop))->additional([
            'message' => __(':0 has been verified successfully.', [$giftshop->name]),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
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
        $this->authorize('can-do', ['giftshop']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = GiftShopModel::find($item);
                if ($item) {
                    $item->items()->delete();
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
            $item = GiftShopModel::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
