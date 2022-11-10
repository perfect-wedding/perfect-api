<?php

namespace App\Http\Resources\v1\User;

use App\Services\AppInfo;
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
            'cover_front' => $this->files['cover_f'] ?? '',
            'cover_back' => $this->files['cover_b'] ?? '',
            'user' => $this->when(
                str($request->route()->getName())->contains(['.albums.show']),
                new UserResource($this->user)
            ),
            'meta' => $this->whenNotNull($this->meta, []),
            'images' => new ImageCollection($this->images),
            'expired' => !$this->expires_at || $this->expires_at->isPast(),
            'expires_at' => $this->expires_at ?? null,
            'share_token' => $this->whenNotNull($this->share_token),
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