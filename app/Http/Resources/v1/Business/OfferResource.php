<?php

namespace App\Http\Resources\v1\Business;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'operator' => $this->operator,
            'amount' => $this->amount,
            'featured' => $this->featured,
            $this->mergeWhen(in_array($route, [
                'account.companies.services.offers.offers',
                'account.companies.services.offers.show',
            ]), [
                'item' => $this->offerable,
            ]),
        ];
    }
}