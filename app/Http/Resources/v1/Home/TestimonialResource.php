<?php

namespace App\Http\Resources\v1\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
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
            'author' => $this->author,
            'title' => $this->title,
            'content' => $this->content,
            'image' => $this->images['image'],
            'last_updated' => $this->updated_at,
        ];
    }
}