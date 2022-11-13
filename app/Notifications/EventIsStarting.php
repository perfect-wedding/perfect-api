<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;
use NotificationChannels\Twilio\TwilioChannel;

class EventIsStarting extends Notification implements ShouldQueue, ShouldRateLimit
{
    use Queueable, RateLimitedNotification;

    protected $event;
    protected $startsIn;
    protected $rateLimitForSeconds = 15;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($event, $startsIn = 30)
    {
        $this->event = $event;
        $this->startsIn = $startsIn;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $pref = config('settings.prefered_notification_channels', ['mail']);
        $channels = in_array('sms', $pref) && in_array('mail', $pref)
            ? ['mail', TwilioChannel::class]
            : (in_array('sms', $pref)
                ? [TwilioChannel::class]
                : ['mail']);

        return collect($channels)
                ->merge(['database'])
                ->all();
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = [
            'name' => $notifiable->firstname,
            'message_line1' => __('Your event ":0" is starting in :1 minutes.', [$this->event->title, $this->startsIn]),
            'message_line2' => __('We hope you are readily prepared to be awesome today.'),
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage)->view(
            ['email', 'email-plain'], $message
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message' => __('Your event \':0\' is starting in :1 minutes.', [$this->event->title, $this->startsIn]),
            'type' => 'event_is_starting',
            'service_order' => [
                'id' => $this->event->id,
                'location' => $this->event->location,
                'start_date' => $this->event->start_date,
                'end_date' => $this->event->end_date,
            ],
        ];
    }
}