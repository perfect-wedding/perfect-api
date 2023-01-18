<?php

namespace App\Http\Resources\v1\Provider;

use App\Http\Resources\v1\Business\GiftShopItemResource;
use App\Http\Resources\v1\Business\InventoryResource;
use App\Http\Resources\v1\Business\ServiceResource;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use App\Models\v1\ShopItem;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $image = ($this->orderable_type === Inventory::class || $this->orderable_type === ShopItem::class
            ? $this->orderable->image_url ?? null
            : $this->orderable->images['image'] ?? null
        );

        if ($this->orderable) {
            $reviewed = $this->when($this->user->id === auth()->id(),
                $this->orderable->whereHas('reviews', function ($q) {
                    $q->whereUserId($this->user->id);
                })->exists(),
                $this->user->reviews()
                    ->whereUserId($request->user()->id)
                    ->whereRelationship(get_class($this->resource).':'.$this->id)->exists()
            );
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'location' => $this->location,
            'destination' => $this->destination,
            'tracking_data' => $this->tracking_data,
            'amount' => $this->amount,
            'qty' => $this->qty,
            'color' => $this->color,
            'code' => $this->code,
            'recieved' => $this->recieved,
            'disputed' => $this->disputed,
            'disputing' => $this->disputing,
            'hasDispute' => $this->disputed || $this->disputing,
            'image' => $this->whenNotNull($image),
            'change_request' => $this->buildChangeRequest($this->changeRequest()->recieved()->first()),
            'request_data' => $this->buildChangeRequest($this->changeRequest) ?? (object) [],
            'waiting' => $this->changeRequest()->sent()->exists(),
            'reviewed' => $reviewed ?? false,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->id == auth()->id() ? 'You' : $this->user->fullname,
                'avatar' => $this->user->avatar,
                'username' => $this->user->username,
                'role' => $this->user->role,
                'role_name' => $this->user->role_name,
            ],
            'date' => $this->due_date,
            $this->mergeWhen(str($request->route()->getName())->contains(['calendar']), [
                'service' => new ServiceResource($this->orderable),
                'className' => 'tf-bg-red text-white',
                'color' => 'white',
                'title' => str($this->user->fullname)->append("'s wedding"),
            ]),
            $this->mergeWhen(! str($request->route()->getName())->contains(['calendar']), [
                'title' => $this->orderable->title ?? $this->orderable->name ?? '',
                'company' => [
                    'id' => $this->company->id ?? null,
                    'name' => $this->company->name ?? null,
                    'slug' => $this->company->slug ?? null,
                    'type' => $this->company->type ?? ($this->orderable_type === ShopItem::class ? 'giftshop' : null),
                    'image' => $this->company->images['image'] ?? null,
                ],
                'item' => $this->orderable_type === Service::class
                    ? new ServiceResource($this->orderable)
                    : ($this->orderable_type === Inventory::class
                        ? new InventoryResource($this->orderable)
                        : new GiftShopItemResource($this->orderable)
                    ),
            ]),
            'type' => $this->orderable_type === Service::class
                ? 'Marketplace'
                : ($this->orderable_type === Inventory::class
                    ? 'Warehouse'
                    : 'Gift Shop'
                ),
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function buildChangeRequest($changeRequest)
    {
        if (! $changeRequest) {
            return null;
        }

        $rid = $changeRequest->rejector['id'] ?? null;

        return [
            'id' => $changeRequest->id,
            'current_status' => $changeRequest->current_status,
            'new_status' => $changeRequest->new_status,
            'status' => $changeRequest->status,
            'reason' => $changeRequest->reason,
            'data' => $changeRequest->data,
            'rejector' => [
                'id' => $rid,
                'name' => $rid == auth()->id() ? 'you' : $changeRequest->rejector['fullname'] ?? null,
                'avatar' => $changeRequest->rejector['avatar'] ?? null,
                'username' => $changeRequest->rejector['username'] ?? null,
            ],
            'sent' => $changeRequest->user_id === auth()->id(),
            'company_type' => $this->company->type ?? ($this->orderable_type === ShopItem::class ? 'giftshop' : null),
            'created_at' => $changeRequest->created_at,
        ];
    }
}
