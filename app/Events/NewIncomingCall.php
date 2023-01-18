<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewIncomingCall implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callData;

    public $receiver_id;

    public $broadcasting_as;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int|string $receiver_id, array $callData)
    {
        $this->receiver_id = $receiver_id;
        $this->callData = $callData;
        $this->broadcasting_as = isset($callData['status']) ? 'call_notifications' : 'incoming_call';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channel = $this->broadcasting_as === 'incoming_call'
            ? 'notifications'
            : 'CallNotifications';

        $channel_id = $this->broadcasting_as === 'incoming_call'
            ? $this->receiver_id
            : $this->callData['id'];

        return [
            new PrivateChannel($channel.'.'.$channel_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return $this->callData;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return $this->broadcasting_as; //'incoming_call';
    }
}
