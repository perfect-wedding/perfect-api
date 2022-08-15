<?php

namespace App\Http\Resources\v1\User;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
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
            'amount' => $this->amount,
            'source' => $this->source,
            'detail' => $this->detail,
            'type' => $this->type,
            'reference' => $this->reference,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i A') : null,
        ];
    }
}
