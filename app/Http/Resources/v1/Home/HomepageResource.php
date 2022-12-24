<?php

namespace App\Http\Resources\v1\Home;

use App\Models\v1\Navigation;
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
                'content' => $this->when(! is_null($this->content), $this->content->mapWithKeys(function ($value, $key) {
                    return [$key => [
                        'id' => $value->id,
                        'slug' => $value->slug,
                        'title' => $value->title,
                        'linked' => $value->linked,
                    ]];
                })),
            ];
        }

        // If landing is true, then we need to pass all pages that are not the default page to the links array
        $links = $this->when(
            $this->landing ?? null,
            Navigation::active()
                ->important()
                ->orderBy('priority')
                ->get()
                ->mapWithKeys(function ($value, $key) {
                    return [$key => [
                        'id' => $value->id,
                        'slug' => $value->slug,
                        'title' => $value->title,
                    ]];
                }));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'meta' => $this->meta,
            'slug' => $this->slug,
            'default' => $this->default,
            'media' => $this->files,
            'details' => $this->details,
            'template' => $this->template,
            'landing' => $this->landing,
            'links' => $links,
            'scrollable' => $this->scrollable,
            'slides' => $this->content ? (new SlidesCollection($this->slides)) : (object) [],
            'content' => $this->content ? (new ContentCollection($this->content)) : (object) [],
            'features' => $this->features ? (new ServiceCollection($this->features)) : (object) [],
            'clients' => $this->features ? (new ServiceCollection($this->clients)) : (object) [],
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
