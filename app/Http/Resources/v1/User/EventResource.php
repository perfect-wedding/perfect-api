<?php

namespace App\Http\Resources\v1\User;

use App\Http\Resources\v1\Concierge\TasksResource;
use App\Http\Resources\v1\Provider\OrderRequestResource;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\Order;
use App\Models\v1\OrderRequest;
use App\Models\v1\Task;
use App\Models\v1\User;
use App\Services\AppInfo;
use Carbon\Carbon;
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
        // Use start date and end date to determine if the event is all day
        $allDay = Carbon::parse($this->start_date)
            ->diffInHours(Carbon::parse($this->end_date)) == 24;

        $company = $this->company_type === User::class
        ? [
            'id' => $this->company->id,
            'name' => $this->company->fullname,
            'intro' => $this->company->intro,
            'slug' => $this->company->username,
            'type' => $this->company->role,
            'logo' => $this->company->avatar,
        ] : [
            'id' => $this->company->id,
            'name' => $this->company->name,
            'intro' => $this->company->intro,
            'slug' => $this->company->slug,
            'type' => $this->company->type,
            'logo' => $this->company->logo,
        ];

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'details' => $this->details,
            'icon' => $this->icon,
            'color' => $this->color,
            'bgcolor' => $this->bgcolor,
            'border_color' => $this->border_color,
            'location' => $this->location,
            'meta' => $this->meta,
            'all_day' => $allDay,
            'duration' => $this->duration ?? 60,
            'time' => $this->when($this->start_date, $this->start_date->format('H:i')),
            'date' => $this->when($this->start_date, $this->start_date->format('Y-m-d')),
            'start_date' => $this->start_date->format('Y-m-d H:i:s'),
            'end_date' => $this->end_date->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'name' => $this->user->fullname,
                'avatar' => $this->user->avatar,
            ],
            'company' => $company,
            $this->mergeWhen(! in_array($this->eventable_type, [Order::class, OrderRequest::class, Task::class]), [
                'type' => 'event',
                'event_type' => 'User Event',
            ]),
            $this->mergeWhen($this->eventable_type === Order::class, [
                'eventable' => OrderResource::make($this->eventable),
                'type' => 'order',
                'event_type' => 'Order',
            ]),
            $this->mergeWhen($this->eventable_type === Task::class, [
                'eventable' => TasksResource::make($this->eventable),
                'type' => 'task',
                'event_type' => 'Concierge Task',
            ]),
            $this->mergeWhen($this->eventable_type === OrderRequest::class, [
                'eventable' => OrderRequestResource::make($this->eventable),
                'type' => 'request',
                'event_type' => 'Service Request',
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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