<?php

namespace App\Http\Resources\v1\User\Messenger;

use App\Http\Resources\v1\User\UserResource;
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
                $this->messageable->username.'-'.str($this->messageable_type)->remove('\\')->toString().'-'.$this->id, true
            ),
            'settings' => $this->settings,
            'user' => new UserResource($this->messageable),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
