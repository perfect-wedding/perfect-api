<?php

namespace App\Http\Resources\v1\Home;

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
        $route = $request->route()->getName();
        if ($route === 'home.list') {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'meta' => $this->meta,
                'slug' => $this->slug,
                'default' => $this->default,
                'scrollable' => $this->scrollable,
                'last_updated' => $this->updated_at,
                'content' => $this->when(!is_null($this->content), $this->content->mapWithKeys(function($value, $key) {
                    return [$key => [
                        'id' => $value->id,
                        'slug' => $value->slug,
                        'title' => $value->title,
                        'linked' => $value->linked,
                    ]];
                })),
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'meta' => $this->meta,
            'slug' => $this->slug,
            'default' => $this->default,
            'scrollable' => $this->scrollable,
            'slides' => (new SlidesCollection($this->slides)),
            'content' => (new ContentCollection($this->content)),
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