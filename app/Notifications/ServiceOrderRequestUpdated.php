<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class ServiceOrderRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $text;
    protected $order;
    protected $status;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order, $status)
    {
        $this->order = $order;
        $this->status = $status;
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

        $status_message = $this->status === 'accepted'
            ? __('accepted, you can now add your item to cart and place your order')
            : __('rejected, please contact the service provider for more information');

        $this->text = __($notifiable->id === $this->order->user_id
            ? 'Your order request (#:code) has been :status.'
            : 'You have :status order request #:code from :user.', [
                'company' => $notifiable->name,
                'user' => $this->order->user->fullname,
                'code' => $this->order->code,
                'item' => $this->order->orderable->name ?? $this->order->orderable->title,
                'status' => $this->status,
            ]);

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
            'message_line1' => __(':message Please login for more information.', ['message' => $this->text]),
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
        $message = __(':message Please login for more information.', ['message' => $this->text]);

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
            'message' => $this->text,
            'type' => 'service_order_request_updated',
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