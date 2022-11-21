<?php

namespace App\Http\Resources\v1\Home\Admin;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageResource extends JsonResource
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
            'meta' => $this->meta,
            'details' => $this->details,
            'media' => $this->files,
            'slug' => $this->slug,
            'default' => $this->default,
            'template' => $this->template,
            'landing' => $this->landing,
            'scrollable' => $this->scrollable,
            'landing' => $this->landing,
            'last_updated' => $this->updated_at,
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