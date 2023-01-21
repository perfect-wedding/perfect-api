<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;

class EscrowFundsReleased extends Notification
{
    use Queueable;

    protected $wallet;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($wallet)
    {
        $this->wallet = $wallet;
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
            'message_line1' => __('Your held escrow funds for order #:order have been released.', [
                'order' => $this->wallet->walletable->code,
            ]),
            'message_line2' => __('You can now request a payout for your funds.'),
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
            'message' => __('Your held escrow funds for order #:order have been released, you can request a payout for your funds.', [
                'order' => $this->wallet->walletable->code,
            ]),
            'type' => 'escrow_funds_released',
            'service_order' => [
                'id' => $this->wallet->walletable->id,
                'user' => $this->wallet->walletable?->user?->fullname,
                'status' => $this->wallet->walletable->status,
                'amount' => $this->wallet->walletable->amount,
                'user_id' => $this->wallet->walletable?->user?->id,
                'service' => $this->wallet->walletable?->orderable?->title,
                'service_id' => $this->wallet->walletable?->orderable?->id,
                'created_at' => $this->wallet->walletable->created_at,
            ],
        ];
    }
}
