<?php

namespace App\Console\Commands;

use App\Models\v1\Call;
use App\Models\v1\EscrowWallet;
use App\Models\v1\Event;
use App\Models\v1\Notification;
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
        $this->deletOldNotifications(config('settings.delete_notifications_after_days', 30));
        $this->holdOrReleaseOrderEscrowFunds();
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
        if (! in_array($newStatus, ['approved', 'declined', 'complete'])) {
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

    /**
     * Fund the escrow wallet or move funds to the user's wallet
     *
     * @return void
     */
    public function holdOrReleaseOrderEscrowFunds()
    {
        // Hold funds
        $orders = Order::where('status', 'in-progress')
            ->whereDoesntHave('escrowWallet')
            ->cursor();

        $count = 0;

        // Loop through all orders and hold funds
        foreach ($orders as $order) {
            $count++;
            $wallet = $order->escrowWallet()->firstOrNew();
            $wallet->user_id = $order->user_id;
            $wallet->walletable_id = $order->id;
            $wallet->walletable_type = Order::class;
            $wallet->transact(
                'Order',
                $order->amount,
                "Escrow held on behalf of order #{$order->code}",
                'credit',
                'held'
            );
            $wallet->user_id = $order->orderable->user_id;
            $wallet->transact(
                'Order',
                $order->amount,
                "Escrow held for order #{$order->code}",
                'debit',
                'held'
            );
        }

        $this->info("$count order(s) escrowed.");
        // End of hold funds

        // Release funds
        $escrowWallets = EscrowWallet::whereHasMorph(
            'walletable',
            Order::class,
            function ($query) {
                $query->where('status', 'completed');
            }
        )->where('status', 'held')->cursor();

        $count = 0;

        // Loop through all escrow wallets and release funds
        foreach ($escrowWallets as $wallet) {
            $count++;
            $wallet->status = 'released';
            $wallet->save();
            if ($wallet->type === 'credit') {
                $wallet->user->useWallet('Order', $wallet->amount, "Escrow released for order #{$wallet->walletable->code}");
                // Remove the 6% commission from the user's wallet
                $commision = 0 - ($wallet->amount * 0.06);
                $wallet->user->useWallet('Order', $commision, "Commission for order #{$wallet->walletable->code}");
                // Notify the user
                // $wallet->user->notify(new OrderStatusChanged($wallet->walletable));
            }
        }

        $this->info("$count order(s) escrow released.");
        // End of release funds
    }

    /**
     * Find all events that are due to be notified and send notifications
     * to the event owner
     *
     * @return void
     */
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

    /**
     * Find all users that are queued for deletion and delete them
     *
     * @return void
     */
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

    /**
     * Find all notifications that are older than {$age} days and delete them
     *
     * @return void
     */
    public function deletOldNotifications($age = 30)
    {
        // Select all notifications that are older than 30 days
        $notifications = Notification::where('created_at', '<=', now()->subDays($age))->cursor();

        $count = 0;

        foreach ($notifications as $notification) {
            $count++;
            $notification->delete();
        }

        $msg = "$count notification(s) deleted.";
        $this->info($msg);
    }

    /**
     * Find all unanswered calls that are older than 3 minutes and terminate them
     *
     * @return void
     */
    public function terminateUnansweredCalls()
    {
        $calls = Call::where('created_at', '<=', now()->subMinutes(3))
            ->whereStartedAt(null)
            ->whereEndedAt(null)
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
