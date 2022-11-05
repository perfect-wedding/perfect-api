<?php

namespace App\Http\Resources\v1\Provider;

use App\Http\Resources\v1\Business\InventoryResource;
use App\Http\Resources\v1\Business\OfferResource;
use App\Http\Resources\v1\Business\ServiceResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status = $this->accepted ? 'accepted' : ($this->rejected ? 'rejected' : 'pending');
        $orderable_title = $this->orderable->title ?? $this->orderable->name ?? '';

        return [
            'id' => $this->id,
            'message' => $this->user->id === auth()->user()->id
                ? __('Your order request for :service is now :status', [
                    'service' => $orderable_title,
                    'status' => $status,
                ])
                : "{$orderable_title} has a new order request from {$this->user->fullname}",
            'accepted' => $this->accepted,
            'rejected' => $this->rejected,
            'destination' => $this->destination,
            'amount' => $this->amount,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->fullname,
                'avatar' => $this->user->avatar,
            ],
            'image' => $this->orderable->images['image'] ?? null,
            'date' => $this->due_date,
            'title' => str($this->user->fullname)->append("'s wedding"),
            'service' => $this->orderable instanceof \App\Models\v1\Service
                ? new ServiceResource($this->orderable)
                : new InventoryResource($this->orderable),
            'request_type' => $this->orderable instanceof \App\Models\v1\Service
                ? 'service'
                : 'inventory',
            'package' => new OfferResource($this->package),
            'provider' => $this->when($this->orderable->company ?? null, [
                'id' => $this->orderable->company->id ?? '',
                'name' => $this->orderable->company->name ?? '',
                'logo' => $this->orderable->company->logo ?? '',
                'slug' => $this->orderable->company->slug ?? '',
            ], []),
            'due_date' => $this->due_date,
            'className' => 'tf-bg-red text-white',
            'color' => 'white',
            'created_at' => $this->created_at,
        ];
    }
}