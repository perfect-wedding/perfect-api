<?php

namespace App\Http\Resources\v1;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
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
            'basic_info' => $this->basic_info,
            'extra_info' => $this->extra_info,
            'features' => $this->features,
            'duration' => $this->duration,
            'tenure' => $this->tenure,
            'price' => $this->price,
            'icon' => $this->icon,
            'cover' => $this->cover_url,
            'trial_days' => $this->trial_days,
            'type' => $this->type,
            'status' => $this->status,
            'popular' => $this->popular,
            'split' => $this->split,
            'annual' => $this->annual,
            'meta' => $this->meta,
            'places' => $this->meta['places'] ?? null,
            'last_updated' => $this->updated_at,
            'created_at' => $this->created_at,
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
