<?php

namespace App\Listeners;

use App\Events\SendingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // $event->channel
        // $event->notifiable
        // $event->notification
        if ($event->channel == 'database') {
            $data = $event->notification->toArray(true);
            $notification = [
                'id' => $event->notification->id ?? null,
                'data' => $data,
                'created_at' => $event->notification->created_at ?? now(),
                'image' => $event->notifiable->image ?? null,
                'message' => $data['message'] ?? '',
                'read_at' => $event->notification->read_at ?? null,
                'type' => $event->notification->type ?? $data['type'] ?? 'default'
            ];
            broadcast(new SendingNotification($notification, $event->notifiable))->toOthers();
        }
    }
}