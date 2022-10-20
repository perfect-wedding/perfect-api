<?php

namespace App\Http\Resources\v1\Business;

use App\Http\Resources\v1\ImageCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use ToneflixCode\LaravelFileable\Media;

class GiftShopItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $route = $request->route()->getName();

        // if (auth()->id()) {
        //     $myPendingOrders = $this->orderRequests()->whereUserId(auth()->id())->pending()->count();
        // }
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'title' => $this->name,
            'stock' => $this->stock,
            'code' => $this->code,
            'price' => $this->price,
            'user_id' => $this->user_id,
            'shop_id' => $this->gift_shop_id,
            'category_id' => $this->category_id,
            'basic_info' => $this->basic_info,
            'details' => $this->details,
            'colors' => $this->colors,
            'provider' => $this->shop->name ?? '',
            $this->mergeWhen(in_array($route, ['giftshops.show', 'giftshops.category', 'giftshops.index']), [
                'company' => new GiftShopResource($this->company),
            ]),
            $this->mergeWhen(auth()->id() &&
            (auth()->id() === $this->user_id || auth()->user()->role === 'admin'), [
                // 'pending_orders' => $this->orderRequests()->pending()->count(),
                // 'accepted_orders' => $this->orderRequests()->accepted()->count(),
                // 'rejected_orders' => $this->orderRequests()->rejected()->count(),
                'category' => $this->category,
            ]),
            // 'my_pending_orders' => $this->when(auth()->id(),
                // $this->orderRequests()->whereUserId(auth()->id() ?? '---')->pending()->count()
            // ),
            // 'my_accepted_orders' => $this->when(auth()->id(),
                // $this->orderRequests()->whereUserId(auth()->id() ?? '---')->accepted()->count()
            // ),
            'image' => $this->images->first() ? $this->images->first()->image_url : (new Media)->getDefaultMedia('default'),
            'image_url' => $this->images->first() ? $this->images->first()->image_url : (new Media)->getDefaultMedia('default'),
            'images' => (new ImageCollection($this->images))->toArray($request),
            'stats' => $this->stats,
            'created_at' => $this->created_at,
            'last_updated' => $this->updated_at,
        ];
    }
}