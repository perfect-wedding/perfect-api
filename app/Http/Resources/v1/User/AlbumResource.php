<?php

namespace App\Http\Resources\v1\User;

use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'privacy' => $this->privacy,
            'info' => $this->info,
            'disclaimer' => $this->disclaimer,
            'meta' => $this->whenNotNull($this->meta, []),
            'images' => $this->images,
        ];
    }
}
