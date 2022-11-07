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
        $type = str($this->transactable ? get_class($this->transactable) : "Unknown")->lower()->explode('\\')->last();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'item' => [
                'id' => $this->transactable->id,
                'slug' => $this->transactable->slug,
                'title' => $this->transactable->title ?? $this->transactable->name,
                'name' => $this->transactable->title ?? $this->transactable->name,
                'image' => $this->whenNotNull($this->transactable->images['image'] ?? null),
                'type' => $type,
            ],
            'amount' => $this->amount,
            'status' => $this->status,
            'method' => $this->method,
            'created_at' => $this->created_at,
            'date' => $this->created_at ? $this->created_at->format('d M, Y h:i A') : 'N/A',
            'company' => $this->transactable->company ? new CompanyResource($this->transactable->company) : [],
            'user' => new UserResource($this->user),
            'route' => $request->route()->getName(),
        ];
    }
}