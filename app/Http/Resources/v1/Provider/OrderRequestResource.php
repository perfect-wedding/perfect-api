<?php

namespace App\Http\Resources\v1\Provider;

use App\Http\Resources\v1\OfferResource;
use App\Http\Resources\v1\ServiceResource;
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
        return [
            'id' => $this->id,
            'message' => $this->user->id === auth()->user()->id
                ? __("Your order request for :service is now :status", [
                    'service' => $this->orderable->title,
                    'status' => $status,
                ])
                : "{$this->orderable->title} has a new order request from {$this->user->fullname}",
            'accepted' => $this->accepted,
            'rejected' => $this->rejected,
            'destination' => $this->destination,
            'amount' => $this->amount,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->fullname,
                'avatar' => $this->user->avatar,
            ],
            'image' => $this->orderable->image_url ?? null,
            'date' => $this->due_date,
            'title' => str($this->user->fullname)->append("'s wedding"),
            'service' => new ServiceResource($this->orderable ?? []),
            'package' => new OfferResource($this->package),
            'provider' => $this->when($this->orderable->company, [
                'id' => $this->orderable->company->id,
                'name' => $this->orderable->company->name,
                'logo' => $this->orderable->company->logo,
                'slug' => $this->orderable->company->slug,
            ], []),
            'due_date' => $this->due_date,
            'className' => 'tf-bg-red text-white',
            'color' => 'white',
            'created_at' => $this->created_at,
        ];
    }
}