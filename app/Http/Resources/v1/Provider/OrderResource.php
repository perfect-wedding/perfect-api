<?php

namespace App\Http\Resources\v1\Provider;

use App\Http\Resources\v1\ServiceResource;
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
        return [
            'id' => $this->id,
            'status' => $this->status,
            'accepted' => $this->accepted && $this->status !== 'rejected',
            'rejected' => !$this->accepted && $this->status === 'rejected',
            'destination' => $this->destination,
            'amount' => $this->amount,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->fullname,
                'avatar' => $this->user->avatar,
            ],
            'date' => $this->due_date,
            'title' => str($this->user->fullname)->append("'s wedding"),
            'service' => new ServiceResource($this->orderable ?? []),
            'due_date' => $this->due_date,
            'className' => 'tf-bg-red text-white',
            'color' => 'white',
            'created_at' => $this->created_at,
        ];
    }
}