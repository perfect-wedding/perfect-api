<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendGiftShopInvite extends Notification implements ShouldQueue
{
    use Queueable;

    protected $link;

    protected $type;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($link, $type = 'send')
    {
        $this->link = $link;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($this->type == 'send') {
            $message = [
                'name' => $notifiable->merchant_name,
                'cta' => ['link' => $this->link, 'title' => 'Accept Invitation'],
                'message_line1' => __('You have been invited to enroll as a Gift Shop merchant on <b>:0</b>, we need to verify that you are still interested. <br /> Click the button below to get started.', [config('settings.site_name')]),
                'message_line2' => __('This invitation will expire in :0 minutes.', [config('settings.token_lifespan', 30)]),
                'message_line3' => 'If you do not recognize this activity or are no longer interested, you do not have to do anything about this.',
                'close_greeting' => __('Regards, <br/>', [config('settings.site_name')]),
                'message_help' => __('If you are unable to click the button, please copy and paste the URL below into your web browser.<br />:0', [$this->link]),
            ];
        } else {
            $message = [
                'name' => $notifiable->merchant_name,
                'message_line1' => __('You have accepted our Gift Shop merchant enrollment invitation, we want to congratulate you and assure you of our absolute support.', [config('settings.site_name')]),
                'message_line2' => __('We will keep you updated on the progress and status of your Gift Shop whenever available, whenever nessesary, we will send you emails with all nessesary information and updates to keep you within the loop.'),
                'close_greeting' => __('Regards, <br/>', [config('settings.site_name')]),
            ];
        }

        return (new MailMessage)
            ->view(['email', 'email-plain'], $message)
            ->subject(__($this->type == 'send'
                ? ':0 Gift Shop merchant invitation'
                : 'Welcome to :0 Gift Shop Program', [config('settings.site_name')]));
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
            //
        ];
    }
}
