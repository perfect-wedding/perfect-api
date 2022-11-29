<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Ensure route is not a listing route
        $canLoad = !str($request->route()->getName())->contains('index');
        $type = str($this->featureable_type)->afterLast('\\')->lower()->__toString();

        return [
            'id' => $this->id,
            'title' => $this->featureable->title ?? $this->featureable->name,
            'info' => $this->featureable->basic_info ?? $this->featureable->intro ?? $this->featureable->short_desc,
            'address' => $this->featureable->full_address ?? null,
            'slug' => $this->featureable->slug ?? $this->featureable->id,
            'bussiness_slug' => $this->when($this->featureable->company, $this->featureable->company?->slug),
            'image' => $this->featureable->image_url ?? $this->featureable->banner_url ?? null,
            'stats' => $this->featureable->stats ?? $this->featureable->stats ?? new \stdClass,
            'type' => $type === 'company' ? $this->featureable->type : $type,
            'duration' => $this->duration,
            'tenure' => $this->when(!!$this->plan, $this->plan->tenure),
            'active' => $this->active,
            'meta' => $this->meta,
            'places' => $this->places,
            'plan' => $this->when($canLoad && !!$this->plan, PlanResource::make($this->plan)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
