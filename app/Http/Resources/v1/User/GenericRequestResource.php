<?php

namespace App\Http\Resources\v1\User;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class GenericRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $ex = ['id', 'company_id', 'firstname', 'lastname', 'username', 'email', 'about', 'intro', 'fullname', 'avatar'];
        return [
            'id' => $this->id,
            'message' => $this->message,
            'status' => $this->status,
            'meta' => $this->meta,
            'mine' => $this->sender === auth()->id(),
            'user' => collect($this->user)->only($ex),
            'sender' => collect($this->sender)->only($ex),
            'updated_at' => $this->updated_at,
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