<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SendVerified extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($type = 'mail')
    {
        $this->type = $type;
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
        return [$this->type, 'database'];
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
            'message_line1' => 'You are receiving this email because you just verified your at account '.config('settings.site_name').' and we want to use this medium to welcome you into our community',
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage)->view(
            ['email', 'email-plain'], $message
        )
        ->subject('Welcome to the '.config('settings.site_name').' community.');
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $n    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($n)
    {
        $message = __('Your :0 account has been verified successfully, welcome to our community.', [config('settings.site_name')]);

        $message = __('Hi :0, ', [$n->firstname]).$message;

        return (new TwilioSmsMessage())
            ->content($message);

        return false;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($n)
    {
        return [
            'type' => 'verification',
            'title' => ($this->type === 'mail') ? 'Email Address Verified' : 'Phone Number Verified',
            'message' => __('Your account has been verified successfully, welcome to our community.'),
        ];
    }
}
