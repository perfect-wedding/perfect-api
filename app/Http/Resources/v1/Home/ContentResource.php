<?php

namespace App\Http\Resources\v1\Home;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
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
            "id" => $this->id,
            "homepage_id" =>  $this->homepage_id,
            "title" =>  $this->title,
            "subtitle" =>  $this->subtitle,
            "content" =>  $this->content,
            "slug" =>  $this->slug,
            "images" =>  $this->images,
            "parent" =>  $this->parent,
            "linked" =>  $this->linked,
            "iterable" =>  $this->iterable,
            'template' => $this->template,
            'page' => $this->page,
            "attached" =>  count($this->attached_model) ? $this->attached_model : null,
            "last_updated" =>  $this->updated_at
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return AppInfo::api();
    }
}