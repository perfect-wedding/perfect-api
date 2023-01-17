<?php

namespace App\Http\Resources\v1\User\Messenger;

use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Service;
use App\Traits\Extendable;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
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
        $participants = $this->participants()->where('user_id', '!=', auth()->id())->get();
        $person = $participants->first();
        $service = $this->type === 'service' && isset($this->data['service']['id']) ? Service::find($this->data['service']['id']) : null;
        $subject = $this->type === 'service' && $service
            ? ($service->company->name ?? $service->title ?? $service->user->fullname)
            : ($participants
                ? str($participants->shuffle()->map(fn ($p) => $p->user->fullname)->implode(', '))
                : $this->subject
            );

        $latestRecieved = $this->messages()->where('user_id', '!=', auth()->id())->latest()->first();
        $avatar = $this->type === 'service' && $service
            ? ($service->company->logo_url ?? $service->image_url ?? $service->user->avatar)
            : ($latestRecieved->user->avatar ?? $participants->first()->avatar ??
                $this->getLatestMessageAttribute()->user->avatar ?? auth()->user()->avatar
            );

        return [
            'id' => $this->id,
            'subject' => str($subject)->limit(25)->prepend($this->type !== 'private' ? $this->subject . ' - ' : '')->toString(),
            'slug' => $this->slug,
            'avatar' => $avatar,
            'type' => $this->type,
            'data' => $this->when($this->type !== 'service' && isset($this->data), $this->data ?? []),
            'service' => $this->when($this->type === 'service' && isset($this->data['service']), $this->data['service'] ?? []),
            'max_participants' => $this->max_participants,
            'participant_ids' => $this->participants->pluck('user_id')->merge([auth()->id()])->unique(),
            'participants' => $this->participantsString(auth()->id()),
            'last_message' => new MessageResource($this->latest_message),
            'reciever' => new UserResource($person ? $person->user : auth()->user()),
            'unread_count' => $this->userUnreadMessagesCount(auth()->id()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            // ...parent::toArray($request)
        ];
    }
}