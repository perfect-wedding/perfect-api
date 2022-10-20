<?php

namespace App\Http\Resources\v1\Business;

use Illuminate\Http\Resources\Json\JsonResource;

class GiftShopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $link = config('settings.frontend_link') .
            '/invitation/giftshop?invited=' .
            base64url_encode(MD5($this->slug) . ':' . $this->invite_code);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'merchant_name' => $this->merchant_name,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'active' => $this->active,
            'socials' => $this->socials ?? [""],
            'logo' => $this->logo_url,
            'invite_link' => $this->when($this->invite_code, $link),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}