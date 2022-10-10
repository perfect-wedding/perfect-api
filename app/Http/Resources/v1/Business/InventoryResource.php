<?php

namespace App\Http\Resources\v1\Business;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'basic_info' => $this->basic_info,
            'short_desc' => str($this->details)->limit(75),
            'details' => $this->details,
            'provider' => $this->company->name ?? '',
            $this->mergeWhen(in_array($route, ['inventories.inventory.show', 'inventories.category']), [
                'company' => new CompanyResource($this->company),
                'offers' => new OfferCollection($this->offers),
            ]),
            $this->mergeWhen(auth()->id() &&
            (auth()->id() === $this->user_id || $this->user_id === auth()->user()->company_id), [
                'pending_orders' => $this->orderRequests()->pending()->count(),
                'accepted_orders' => $this->orderRequests()->accepted()->count(),
                'rejected_orders' => $this->orderRequests()->rejected()->count(),
                'category' => $this->category,
            ]),
            'my_pending_orders' => $this->when(auth()->id(),
                $this->orderRequests()->whereUserId(auth()->id() ?? '---')->pending()->count()
            ),
            'my_accepted_orders' => $this->when(auth()->id(),
                $this->orderRequests()->whereUserId(auth()->id() ?? '---')->accepted()->count()
            ),
            'image' => $this->images['image'] ?? '',
            'image_url' => $this->images['image'] ?? '',
            'stats' => $this->stats,
            'created_at' => $this->created_at,
            'last_updated' => $this->updated_at,
        ];
    }
}
