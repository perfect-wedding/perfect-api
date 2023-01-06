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

class OrderStatusChanged extends Notification implements ShouldQueue, ShouldRateLimit
{
    use Queueable, RateLimitedNotification;

    /**
     * New status: When this is present, the order's status
     * will be changed after the request is accepted.
     *
     * @return void
     */
    protected $ns;

    protected $text;

    protected $admin;

    protected $order;

    protected $status;

    protected $statusName;

    protected $rateLimitForSeconds = 15;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order, $ns = null, $admin = false)
    {
        $this->ns = $ns;
        $this->admin = $admin;
        $this->order = $order;
        $this->status = $order->status;
        $this->statusName = [
            'pending' => __('Pending'),
            'accepted' => __('Accepted'),
            'rejected' => __('Rejected'),
            'cancelled' => __('Cancelled'),
            'completed' => __('Completed and closed'),
            'delivered' => __('Delivered'),
            'in-progress' => __('Being processed'),
        ][$ns ?? $this->status];

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

        $rejected = $this->order->changeRequest->status == 'rejected' || $this->order->changeRequest->status == 'disputed';
        $params = [
            'company' => $notifiable->name,
            'user' => $this->order->user->fullname,
            'code' => $this->order->code,
            'item' => $this->order->orderable->name ?? $this->order->orderable->title,
            'status' => str($this->statusName)->lower(),
            'reason' => $this->order->changeRequest->reason && !!$rejected
                ? __(' Reason for rejection: :0', [0 => $this->order->changeRequest->reason])
                : '',
        ];

        if ($this->ns) {
            $this->text = $notifiable->id === $this->order->user_id
                ? __('Your request to change the order #:code status to :status has been sent and is awaiting confirmation.', $params)
                : __(':user marked order #:code as :status.:reason', $params);
        } else {
            $this->text = $notifiable->id === $this->order->user_id
                ? __('Your order (#:code) is now :status.', $params)
                : __('Order (#:code) from :user is now :status', $params);
        }

        if ($this->admin) {
            $this->text = __('Order #:code status has been changed to :status by a service moderator.', $params);
        }

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
            'type' => 'order_status_changed',
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
