<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class NewServiceOrderRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

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
            'name' => $notifiable->user->firstname,
            'message_line1' => __(':company has a new service order request from :user, please login to respond.', [
                'company' => $notifiable->name,
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
        $message = __('Hi :provider, :company has a new service order request from :user, please login to respond.', [
            'provider' => $notifiable->user->firstname,
            'company' => $notifiable->name,
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
            'message' => __(':company has a new service order request from :user.', [
                'company' => $notifiable->name,
                'user' => $this->order->user->fullname,
            ]),
            'type' => 'service_order',
            'service_order' => [
                'id' => $this->order->id,
                'user' => $this->order->user->fullname,
                'status' => $this->order->status,
                'amount' => $this->order->amount,
                'user_id' => $this->order->user->id,
                'service' => $this->order->orderable->title,
                'service_id' => $this->order->orderable->id,
                'created_at' => $this->order->created_at,
            ],
        ];

        return $notification_array;
    }
}
