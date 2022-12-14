<?php

namespace App\Console\Commands;

use App\Models\v1\Call;
use App\Models\v1\Event;
use App\Models\v1\Order;
use App\Models\v1\Task;
use App\Models\v1\User;
use App\Models\v1\Wallet;
use App\Notifications\OrderStatusChanged;
use App\Notifications\RefundCompleted;
use Illuminate\Console\Command;

class Automate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:automate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command automates the system and ensures that all services requiring automation are run';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->updateTasks();
        $this->processRefunds();
        $this->startPendingOrders();
        $this->notifyOfEvents();
        $this->deleteQueuedUsers();
        $this->processPayouts();
        $this->processPayouts('complete');
        $this->processPayouts('declined');
        $this->terminateUnansweredCalls();
        $this->info('All tasks completed.');

        return 0;
    }

    /**
     * Check for timed out tasks and set status to "timeout"
     *
     * @return void
     */
    protected function updateTasks()
    {
        $tasks = Task::available(false)->cursor();

        $count = 0;

        foreach ($tasks as $task) {
            $count++;
            $task->status = 'timeout';
            $task->save();

            $company = $task->company;
            $company->status = 'unverified';
            $company->save();

            $company->verification && $company->verification->delete();
        }

        $msg = "$count task(s) updated.";
        $this->info($msg);
    }

    /**
     * Find all orders that have been cancelled and process refunds
     *
     * @return void
     */
    protected function processRefunds()
    {
        $orders = Order::where('status', 'cancelled')->whereRaw('`refund` < `amount`')->cursor();

        $count = 0;

        foreach ($orders as $order) {
            $count++;
            $order->refund = $order->amount;
            $order->save();

            // Refund amount to the user's wallet
            $order->user->useWallet('Refunds', $order->amount, "Refund for order #{$order->code}");

            // Send notifications for refunds
            $order->orderable->user->notify(new RefundCompleted($order, 'order_cancelled'));
            $order->user->notify(new RefundCompleted($order));
        }

        $msg = "$count order(s) refunded.";
        $this->info($msg);
    }

    /**
     * Process all user withdrawal requests
     */
    public function processPayouts($newStatus = 'approved')
    {
        if (!in_array($newStatus, ['approved', 'declined', 'complete'])) {
            $this->error('Invalid status provided. Must be either "approved", "complete" or "declined"');

            return;
        }

        $currStatus = $newStatus === 'approved'
            ? 'pending'
            : ($newStatus === 'complete'
                ? 'approved'
                : $newStatus
            );

        $wallets = Wallet::whereStatus($currStatus)
            ->whereNot('escaped', true)
            ->whereType('withdrawal')->cursor();

        $count = 0;

        foreach ($wallets as $wallet) {
            $count++;
            $wallet->status = $newStatus;

            if ($newStatus === 'declined' || $newStatus === 'failed') {
                $wallet->escaped = true;
                $wallet->user->useWallet('Refunds', $wallet->amount, "Refund for declined withdrawal request #{$wallet->reference}.");
            }

            $wallet->save();
        }

        $msg = "{$count} withdrawal requests(s) processed and {$newStatus}.";
        $this->info($msg);
    }

    /**
     * Find all pending orders older than conf('album_link_duration', 24) hours and
     * move them to the "in-progress" status
     *
     * @return void
     */
    public function startPendingOrders()
    {
        // Fetch pending orders that are up to 24hrs old
        $orders = Order::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(conf('order_cancel_window', 24)))
            ->cursor();

        $count = 0;

        foreach ($orders as $order) {
            $count++;
            $order->status = 'in-progress';
            $order->save();
            $order->user->notify(new OrderStatusChanged($order));
            $order->orderable->user->notify(new OrderStatusChanged($order));
        }

        $msg = "$count order(s) started.";
        $this->info($msg);
    }

    public function notifyOfEvents($startsIn = 30)
    {
        // Select all events that are due to be notified (start_date <= now() - 30 mins && end_date >= now() && notify == 1)
        $events = Event::where('start_date', '<=', now()->subMinutes($startsIn))
            ->where('end_date', '>=', now())
            ->where('notify', 1)
            ->cursor();

        $count = 0;

        foreach ($events as $event) {
            $count++;
            if ($event->company->user) {
                $event->company->user->notify(new \App\Notifications\EventIsStarting($event, $startsIn));
            } else {
                $event->company->notify(new \App\Notifications\EventIsStarting($event, $startsIn));
            }
            $event->notify = false;
            $event->save();
        }

        $msg = "$count event(s) started.";
        $this->info($msg);
    }

    public function deleteQueuedUsers()
    {
        // Select all users that are queued for deletion (updated_at <= now() - 2 hours)
        $users = User::whereHidden(true)
            ->where('updated_at', '<=', now()->subHours(2))
            ->cursor();

        $count = 0;

        foreach ($users as $user) {
            $count++;
            $user->markAccountAsVerified(false, true);
            $user->companies()->delete();
            $user->orders()->delete();
            $user->albums()->delete();
            $user->boards()->delete();
            $user->events()->delete();
            $user->delete();
        }
        $msg = "$count user(s) deleted.";
        $this->info($msg);
    }

    public function terminateUnansweredCalls()
    {
        $calls = Call::where('created_at', '<=', now()->subMinutes(3))
            ->whereStartedAt(NULL)
            ->whereEndedAt(NULL)
            ->whereAcceptedParticipantIds('[]')
            ->cursor();

        $count = 0;

        foreach ($calls as $call) {
            $count++;
            $call->missed_participant_ids = $call->participant_ids;
            $call->ended_at = now();
            $call->save();
        }

        $msg = "$count call(s) terminated.";
        $this->info($msg);
    }
}