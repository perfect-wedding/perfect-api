<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class ServiceOrderSuccess extends Notification implements ShouldQueue, ShouldRateLimit
{
    use Queueable, RateLimitedNotification;

    protected $order;
    protected $rateLimitForSeconds = 15;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
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
            'message_line1' => __($notifiable->id === $this->order->user_id
            ? 'Your service order request from :company  was successfull, we will inform you when your request status changes.'
            : ':company has a new service order request from :user, please login to respond.', [
                'company' => $this->order->orderable->company->name,
                'user' => $this->order->user->fullname,
            ]),
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
        $message = __($notifiable->id === $this->order->user_id
        ? 'Hi :user, your service order request from :company was successfull, we will inform you when your request status changes.'
        : ':Hi :provider, :company has a new service order request from :user, please login to respond.', [
            'provider' => $this->order->orderable->company->user->firstname,
            'company' => $this->order->orderable->company->name,
            'user' => $this->order->user->fullname,
        ]);

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
            'message' => __($notifiable->id === $this->order->user_id
            ? 'Your service order request from :company was successfull, we will inform you when your request status changes.'
            : ':company has a new service order request from :user.', [
                'company' => $this->order->orderable->company->name,
                'user' => $this->order->user->fullname,
            ]),
            'type' => 'service_order',
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