<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\Business\CompanyResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
        $previleged = (
            $request->user()->id === $this->id
        );

        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->fullname,
            'about' => $this->about,
            'intro' => $this->intro,
            'status_message' => $this->status_message,
            'avatar' => $this->avatar,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'verified' => $this->verified,
            'role_name' => $this->role_name,
            'type' => $this->type,
            $this->mergeWhen($previleged && ! in_array($route, []) && ! str($route)->contains(['messenger.', 'vision.']), [
                'company' => new CompanyResource($this->company),
                'dob' => $this->dob,
                'address' => $this->address,
                'email' => $this->email,
                'phone' => $this->phone,
                'role' => $this->role,
                'role_route' => $this->role_route,
                'last_attempt' => $this->last_attempt,
                'email_verified_at' => $this->email_verified_at,
                'phone_verified_at' => $this->phone_verified_at,
                'basic_stats' => $this->basicStats,
                'privileges' => $this->privileges,
                'settings' => $this->settings,
                'identity' => $this->identity,
            ]),
            $this->mergeWhen($request->user()->id === $this->id || $request->user()->role === 'admin', [
                'wallet_bal' => $this->when($request->user()->id === $this->id, $this->wallet_bal),
                'bank_name' => $this->bank_name,
                'bank_account_name' => $this->bank_account_name,
                'bank_account_number' => $this->bank_account_number,
            ]),
            'reg' => $this->created_at,
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
