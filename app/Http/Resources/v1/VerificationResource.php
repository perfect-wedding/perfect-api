<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\User\UserResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $adv = ! in_array($request->route()->getName(), ['concierge.companies.verify']);
        return [
            'id' => $this->id,
            'user' => $this->when($adv, new UserResource($this->user)),
            'status' => $this->status,
            'exists' => $this->exists,
            'rejected_docs' => $this->rejected_docs,
            'company' =>  $this->when($adv, new CompanyResource($this->company)),
            'concierge' => $this->when($adv, new UserResource($this->concierge)),
            'images' => $this->images,
            'observations' => $this->observations,
            'real_address' => $this->real_address,
            'apply_date' => $this->created_at,
            'verify_date' => $this->updated_at,
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
