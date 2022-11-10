<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\Business\ServiceResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'message' => $this->data['message'] ?? '',
            'type' => $this->data['type'] ?? '',
            'image' => ($this->data['type'] ?? '') === 'service_order'
                ? ($this->order->orderable->image_url ?? '')
                : $this->data['user']['avatar'] ?? '',
            $this->mergeWhen(($this->data['type'] ?? '') === 'service_order', [
                'service_order' => [
                    'id' => $this->order->id ?? '',
                    'status' => $this->order->status ?? '',
                    'accepted' => ($this->order->accepted ?? '') && ($this->order->status ?? '') !== 'rejected',
                    'rejected' => ! ($this->order->accepted ?? '') && ($this->order->status ?? '') === 'rejected',
                    'destination' => $this->order->destination ?? '',
                    'amount' => $this->order->amount ?? '',
                    'user' => [
                        'id' => $this->order->user->id ?? '',
                        'name' => $this->order->user->fullname ?? '',
                        'avatar' => $this->order->user->avatar ?? '',
                    ],
                    'service' => $this->when($this->order->orderable ?? null, new ServiceResource($this->order->orderable ?? []), []),
                    'created_at' => $this->order->created_at ?? '',
                    'due_date' => $this->order->due_date ?? '',
                ],
            ]),
            'data' => $this->when(($this->data['type'] ?? '') !== 'service_order', $this->data ?? []),
            'read_at' => $this->read_at,
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