<?php

namespace App\Http\Resources\v1\Business;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'description_elipses' => str($this->description)->limit(60),
            'companies' => $this->when(in_array($route, ['categories.show']), $this->companies),
            'priority' => $this->priority,
            'stats' => $this->stats,
            'type' => $this->type,
            'image' => $this->image_url,
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