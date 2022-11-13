<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class AdvertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = $request->user();
        if (!$user) {
            $user = new \stdClass;
            $user->fullname = 'Guest';
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'title' => str($this->title)->replace('{user}', $user->fullname)->replace('name', $user->fullname),
            'details' => str($this->details)->replace('{user}', $user->fullname)->replace('name', $user->fullname),
            'media' => $this->get_files['media']['url'] ?? '',
            'preview' => $this->get_files['thumbnail']['url'] ?? '',
            'is_image' => $this->get_files['media']['isImage'] ?? false,
            'url' => $this->url,
            'active' => $this->active,
            'places' => $this->places,
            'meta' => $this->meta,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
