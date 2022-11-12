<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\Provider\OrderRequestResource;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\Order;
use App\Models\v1\OrderRequest;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            "id" => $this->id,
            "slug" => $this->slug,
            "title" => $this->title,
            "details" => $this->details,
            "icon" => $this->icon,
            "color" => $this->color,
            "bgcolor" => $this->bgcolor,
            "border_color" => $this->border_color,
<<<<<<< HEAD
            "location" => $this->location,
            "meta" => $this->meta,
            "duration" => $this->duration ?? 60,
=======
            "meta" => $this->meta,
            "duration" => $this->duration,
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            "time" => $this->when($this->start_date, $this->start_date->format('H:i')),
            "date" => $this->when($this->start_date, $this->start_date->format('Y-m-d')),
            "start_date" => $this->start_date,
            "end_date" => $this->end_date,
            "user" => [
                'id' => $this->user->id,
                'name' => $this->user->fullname,
            ],
            "company" => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'type' => $this->company->type,
            ],
            $this->mergeWhen($this->eventable_type !== Order::class && $this->eventable_type !== OrderRequest::class, [
                'type' => 'event',
                'event_type' => 'Simple Event',
            ]),
            $this->mergeWhen($this->eventable_type === Order::class, [
<<<<<<< HEAD
                'eventable' => OrderResource::make($this->eventable),
=======
                'order' => OrderResource::make($this->eventable),
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
                'type' => 'order',
                'event_type' => 'Order',
            ]),
            $this->mergeWhen($this->eventable_type === OrderRequest::class, [
<<<<<<< HEAD
                'eventable' => OrderRequestResource::make($this->eventable),
=======
                'request' => OrderRequestResource::make($this->eventable),
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
                'type' => 'request',
                'event_type' => 'Service Request',
            ]),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
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
