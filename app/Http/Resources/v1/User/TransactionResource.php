<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\CompanyResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'reference' => $this->reference,
            'item' => [
                'id' => $this->transactable->id,
                'slug' => $this->transactable->slug,
                'title' => $this->transactable->title,
            ],
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'date' => $this->created_at ? $this->created_at->format('d M, Y h:i A') : 'N/A',
            'company' => new CompanyResource($this->transactable->company),
            'user' => new UserResource($this->user),
        ];
    }
}
