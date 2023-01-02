<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;

class OrderIsBeingDisputed extends Notification
{
    use Queueable;

    protected $text;
    protected $order;
    protected $opened;
    protected $byBusiness;

    protected $rateLimitForSeconds = 15;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order, $opened = true, $byBusiness = null)
    {
        $this->order = $order;
        $this->opened = $opened;
        $this->byBusiness = $byBusiness;
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
            'message_line1' => __(':message Please login for more information.', ['message' => $this->msg($notifiable)]),
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage)->view(
            ['email', 'email-plain'], $message
        )->subject( __($this->opened ? 'Order is being disputed' : 'Order dispute has been closed') );
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
            'message' => $this->msg($notifiable),
            'type' => 'order_disputed',
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

    protected function msg($notifiable)
    {
        $params = [
            'comp' => $this->order->company->name, // Company name
            'cu' => $this->byBusiness->fullname ?? null, // Company user
            'user' => $this->order->user->fullname,
            'code' => $this->order->code,
            'item' => $this->order->orderable->name ?? $this->order->orderable->title,
            'status' => $this->opened ? __('opened') : __('closed'),
        ];

        if ($this->byBusiness) {
            $this->text = $notifiable->id === $this->order->user_id
                ? __('You opened a dispute on order #:code on behalf of :comp, you will be contacted by support for further actions.', $params)
                : __(':cu opened a dispute on order #:code on behalf of comp:, you will be contacted by support for further actions.', $params);
        } elseif ($this->opened) {
            $this->text = $notifiable->id === $this->order->user_id
                ? __('You opened a dispute on order #:code, you will be contacted by support for further actions.', $params)
                : __(':user is disputing order #:code, you will be contacted by support for further actions.', $params);
        } else {
            $this->text = __('Dispute for order #:code has been closed, thanks for your patience.', $params);
        }

        return $this->text;
    }
}