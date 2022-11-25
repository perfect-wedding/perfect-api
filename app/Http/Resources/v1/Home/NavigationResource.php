<?php

namespace App\Http\Resources\v1\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class NavigationResource extends JsonResource
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
            'index' => !!$this->navigable->default,
            'tree' => $this->tree,
            'group' => $this->group,
            'active' => $this->active,
            'location' => $this->location,
            'important' => $this->important,
            'type' => str($this->navigable_type)->afterLast('\\'),
            'navigable_id' => $this->navigable_id,
        ];
    }
}
