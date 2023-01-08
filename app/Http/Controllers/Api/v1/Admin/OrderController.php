<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\Order;
use App\Models\v1\Service;
use App\Notifications\OrderIsBeingDisputed;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', [$request->origin == 'dashboard' ? 'dashboard' : 'orders.list']);
        $query = Order::query();

        // Reorder Columns //
        if ($request->order && $request->order === 'latest') {
            $query->latest();
        } elseif ($request->order && $request->order === 'oldest') {
            $query->oldest();
        } elseif ($request->order && is_array($request->order)) {
            foreach ($request->get('order', []) as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        if ($request->has('status') && in_array($request->status, [
            'rejected', 'requesting', 'pending', 'in-progress', 'delivered', 'completed', 'cancelled',
        ])) {
            $query->whereStatus($request->status);
        }

        $orders = $query
            ->paginate($request->get('limit', 15))
            ->withQueryString();

        return (new OrderCollection($orders))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('can-do', ['orders.update']);

        $this->validate($request, [
            'status' => 'required|in:pending,in-progress,delivered,completed,cancelled,close_dispute',
        ], [
            'status.required' => 'Status is required',
            'status.in' => 'Status is invalid',
        ]);

        if ($request->status == 'cancelled') {
            $message = __('Order #:0 has been cancelled successfully, refund is now being processed.', [$order->id]);
        } elseif ($order->status == 'pending' && $request->status == 'in-progress') {
            $message = __('Order #:0 is now in progress.', [$order->id]);
        } elseif ($order->status == 'in-progress' && $request->status == 'delivered') {
            $message = __('Order #:0 is now delivered.', [$order->id]);
        } elseif ($order->status == 'delivered' && $request->status == 'completed' && $order->orderable_type == Service::class) {
            $message = __('Order #:0 is now completed.', [$order->id]);
        } else {
            $message = __('Order #:0 is now :1.', [$order->id, $request->status]);
        }

        if ($request->status == 'close_dispute') {
            $disput = $order->changeRequest()->first();
            $disput->delete();

            $order->user->notify(new OrderIsBeingDisputed($order, false));
            $order->orderable->user->notify(new OrderIsBeingDisputed($order, false));

            $message = __('Dispute on order #:0 is now closed.', [$order->id]);
        } else {
            $order->status = $request->status;
            $order->save();

            $order->user->notify(new OrderStatusChanged($order, $request->status, true));
            $order->orderable->user->notify(new OrderStatusChanged($order, $request->status, true));
        }

        return (new OrderResource($order))->additional([
            'message' => $message,
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}