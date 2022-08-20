<?php

namespace App\Http\Resources\v1\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferingResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'icon' => $this->icon,
            'images' => $this->images,
            'features' => $this->features,
            'last_updated' => $this->updated_at,
        ];
    }
}