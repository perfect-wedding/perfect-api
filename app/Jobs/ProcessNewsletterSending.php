<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessNewsletterSending implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $items;

    protected $newsletter;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($items, $newsletter)
    {
        $this->items = $items;
        $this->newsletter = $newsletter;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Send email to each recipient
        foreach ($this->items as $item) {
            $email   = $item->email;
            $subject = $this->newsletter->subject ?? __(':0 Newsletter', [config('settings.site_name')]);

            $data = [
                'name' => $item->name,
                'message_line1' => $this->newsletter->message,
                'close_greeting' => 'Regards, <br/>' . config('settings.site_name'),
            ];

            $sent = Mail::send(['email', 'email-plain'], $data, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });

            // Check if this is the last item
            if ($item->id === $this->items->last()->id) {
                $this->newsletter->status = $sent ? 'sent' : 'failed';
                $this->newsletter->save();
            }
        }
    }
}
