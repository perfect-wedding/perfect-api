<?php

namespace App\Http\Resources\v1\Concierge;

use App\Http\Resources\v1\CompanyResource;
use App\Http\Resources\v1\User\UserStripedResource;
use App\Http\Resources\v1\VerificationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TasksResource extends JsonResource
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
            'status' => $this->status,
            'business' => new CompanyResource($this->company),
            'concierge' => new UserStripedResource($this->concierge),
            'verification' => new VerificationResource($this->company->verification),
            'released' => ! $this->timer_active,
            'time_left' => $this->time_left,
            'ends_at' => $this->ends_at,
            'book_date' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
