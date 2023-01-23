<?php

namespace App\Http\Resources\v1\User\Messenger;

use App\Http\Resources\v1\User\UserResource;
use App\Services\AppInfo;
use App\Traits\Extendable;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipationResource extends JsonResource
{
    use Extendable;

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
            'conversation_id' => $this->parseConversationId(
                $this->user->username.'-'.str(User::class)->remove('\\')->toString().'-'.$this->id, true
            ),
            'thread_id' => $this->thread_id,
            'starred' => $this->starred,
            'settings' => $this->settings,
            'user' => new UserResource($this->user),
            'last_read' => $this->last_read,
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