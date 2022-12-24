<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class RefundCompleted extends Notification implements ShouldQueue, ShouldRateLimit
{
    use Queueable, RateLimitedNotification;

    protected $type = 'order_cancelled';

    protected $order;

    protected $message;

    protected $rateLimitForSeconds = 15;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order, $type = 'refund_completed')
    {
        $this->order = $order;
        $this->type = $order;

        $this->message = $type === 'refund_completed'
            ? __('You recently cancelled your order for :0 and your refund has now been proccessed.', [$this->order->orderable->name ?? $this->order->orderable->title])
            : __(':0 has cancelled thier order for :1.', [$this->order->user->fullname, $this->order->orderable->name ?? $this->order->orderable->title]);

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
            'message_line1' => $this->message,
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage)->view(
            ['email', 'email-plain'], $message
        );
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($notifiable)
    {
        $message = $this->message;

        return (new TwilioSmsMessage())->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $notification_array = [
            'message' => $this->message,
            'type' => $this->type,
            'image' => $this->order->orderable->image_url,
            'service_order' => [
                'id' => $this->order->id,
                'user' => $this->order->user->fullname,
                'amount' => $this->order->amount,
                'status' => $this->order->status,
                'user_id' => $this->order->user_id,
                'service' => $this->order->orderable->title,
                'service_id' => $this->order->orderable->id,
                'created_at' => $this->order->created_at,
                'location' => $this->order->location,
                'destination' => $this->order->destination,
            ],
        ];

        return $notification_array;
    }
}
