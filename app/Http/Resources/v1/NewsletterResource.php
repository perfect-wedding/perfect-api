<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\User\UserStripedResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterResource extends JsonResource
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
            'type' => $this->type,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'recipients' => $this->recipients,
            'sender' => new UserStripedResource($this->sender),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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