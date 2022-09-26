<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\Business\CompanyResource;
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
        $type = $this->transactable instanceof \App\Models\v1\Service
            ? 'service'
            : 'inventory';

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'item' => [
                'id' => $this->transactable->id,
                'slug' => $this->transactable->slug,
                'title' => $this->transactable->title ?? $this->transactable->name,
                'name' => $this->transactable->title ?? $this->transactable->name,
                'type' => $type,
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