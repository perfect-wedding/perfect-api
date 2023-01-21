<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\Business\CompanyResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $transactable = $this->transactable ?? null;
       return $type = str($transactable ? get_class($transactable) : 'Unknown')->lower()->explode('\\')->last();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'item' => [
                'id' => $transactable->id ?? '',
                'slug' => $transactable->slug ?? '',
                'title' => $transactable->title ?? $transactable->name ?? '',
                'name' => $transactable->title ?? $transactable->name ?? '',
                'image' => $this->whenNotNull($transactable->images['image'] ?? null),
                'type' => $type,
            ],
            'items' => $this->when(str($request->route()->getName())->contains('admin.'), $transactable),
            'user' => $this->when(str($request->route()->getName())->contains('admin.'), [
                'id' => $this->user->id,
                'name' => $this->user->fullname,
                'avatar' => $this->user->avatar,
                'username' => $this->user->username,
                'role' => $this->user->role,
                'role_name' => $this->user->role_name,
                'type' => $this->user->type,
                'company' => $this->user->company,
            ]),
            'amount' => $this->amount,
            'status' => $this->status,
            'method' => $this->method,
            'created_at' => $this->created_at,
            'date' => $this->created_at ? $this->created_at->format('d M, Y h:i A') : 'N/A',
            'company' => $transactable && $transactable->company ? new CompanyResource($transactable->company) : [],
            // 'user' => new UserResource($this->user),
            'route' => $request->route()->getName(),
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