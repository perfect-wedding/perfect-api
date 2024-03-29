<?php

namespace App\Http\Resources\v1\User;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class CallingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $num_seconds = $this->when($this->ended_at, $this->started_at?->diffInSeconds($this->ended_at), 0);

        $seconds = $num_seconds % 60;
        $min = floor($num_seconds / 60);
        if ($min == 0) {
            $duration = "00:{$seconds}";
        } else {
            $duration = "{$min}:{$seconds}";
        }

        return [
            'id' => $this->id,
            'meta' => $this->meta,
            'subject' => $this->caller->id == auth()->id() ? 'Call from you' : $this->subject,
            'room_name' => $this->room_name,
            'room_pass' => $this->room_pass,
            'type' => $this->type,
            'origin' => $this->origin,
            'duration' => $this->when($this->ended_at, $duration),
            'ongoing' => $this->ongoing,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'participants' => $this->participants,
            'missed_participants' => $this->missedParticipants,
            'rejected_participants' => $this->rejectedParticipants,
            'accepted_participants' => $this->acceptedParticipants,
            'event' => new EventResource($this->event),
            'caller' => new UserResource($this->caller),
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