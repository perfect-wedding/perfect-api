<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SendDBNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $notification_array;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($array)
    {
        $this->notification_array = $array;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        // Log::debug('Notification: '.$this->notification_array['message'], $this->notification_array);

        return $this->notification_array ?? ['message' => 'ddd'];
    }
}