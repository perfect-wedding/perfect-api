<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\User\UserResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'user_id' => $this->user_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'intro' => $this->intro,
            'intro_elipses' => str($this->intro)->words(7),
            'address_elipses' => str($this->address)->words(7),
            'booked' => $this->when(!!$this->task, true),
            'about' => $this->about,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'postal' => $this->postal,
            'address' => $this->address,
            'role' => $this->role,
            $this->mergeWhen($request->user()->id === $this->user_id || $request->user()->role === 'concierge', [
                'verified_data' => $this->verified_data,
                'rc_number' => $this->rc_number,
                'rc_company_type' => $this->rc_company_type,
            ]),
            'banner' => $this->banner_url,
            'logo' => $this->logo_url,
            'status' => $this->status,
            'stats' => $this->stats,
            'rating' => 5,
            'created_at' => $this->created_at,
            'user' => $this->when(
                $request->user()->id !== $this->user_id &&
                ! in_array($route, ['services.service.show']), function () {
                    return new UserResource($this->user);
            }),
            'task' => $this->when(
                !!$this->task && !!auth()->user() &&
                $this->task->concierge_id === auth()->user()->id,
                $this->task
            ),
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