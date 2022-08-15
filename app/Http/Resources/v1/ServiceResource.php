<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'stock' => $this->stock,
            'price' => $this->price,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'basic_info' => $this->basic_info,
            'short_desc' => $this->short_desc,
            'details' => $this->details,
            'provider' => $this->company->name,
            $this->mergeWhen(in_array($route, ['services.service.show']), [
                'company' => new CompanyResource($this->company),
                'offers' => new OfferCollection($this->offers),
            ]),
            'image' => $this->image_url,
            'image_url' => $this->image_url,
            'stats' => $this->stats,
            'created_at' => $this->created_at,
            'last_updated' => $this->updated_at,
        ];
    }
}
