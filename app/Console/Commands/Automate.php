<?php

namespace App\Console\Commands;

use App\Models\v1\Order;
use App\Models\v1\Task;
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

    protected function processRefunds()
    {
        $orders = Order::where('status', 'cancelled')->whereRaw('`refund` < `amount`')->cursor();

        $count = 0;

        foreach ($orders as $order) {
            $count++;
            $order->refund = $order->amount;
            $order->user->notify(new RefundCompleted($order));
            $order->orderable->user->notify(new RefundCompleted($order, 'order_cancelled'));
            $order->save();
        }

        $msg = "$count order(s) refunded.";
        $this->info($msg);
    }
}