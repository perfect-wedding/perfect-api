<?php

namespace App\Http\Resources\v1\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class SlidesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $route = $request->route()->getName();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'color' => $this->color,
            'image' => $this->images['image'],
            'responsive_images' => $this->responsive_images['image'],
            'page' => $this->when(!in_array($route, ['home.index']), $this->page),
            'last_updated' => $this->updated_at,
        ];
    }
}