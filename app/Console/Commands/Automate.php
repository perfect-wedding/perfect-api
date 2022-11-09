<?php

namespace App\Console\Commands;

use App\Models\v1\Order;
use App\Models\v1\Task;
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
}
