<?php

namespace App\Http\Resources\v1\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'role' => $this->role,
            'info' => $this->info,
            'socials' => $this->socials,
            'image' => $this->images['image'],
            'last_updated' => $this->updated_at,
        ];
    }
}