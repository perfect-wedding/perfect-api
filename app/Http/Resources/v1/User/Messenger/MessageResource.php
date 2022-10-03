<?php

namespace App\Http\Resources\v1\User\Messenger;

use App\Http\Resources\v1\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'sent' => $this->user->id === auth()->user()->id,
            'message' => $this->body,
            'text' => $this->body,
            'caption' => str($this->body)->words(5)->toString(),
            'created_at' => $this->created_at,
            'avatar' => $this->user->avatar,
            'thread_id' => $this->thread_id,
            'slug' => $this->thread->slug,
            'data' => $this->data,
            'conversation_id' => $this->thread->id,
            'name' => $this->user->fullname,
            'sender' => new UserResource($this->user),
            'type' => $this->type ? $this->type : 'text',
            'stamp' => $this->created_at,
            // ...parent::toArray($request),
        ];
    }
}
