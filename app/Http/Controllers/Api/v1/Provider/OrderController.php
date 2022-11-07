<?php

namespace App\Http\Controllers\Api\v1\Provider;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Auth()->user()->company->orders()->cancelled(false)->orderByDesc('id');

        if ($request->has('status') && in_array($request->status, ['pending', 'in-progress', 'accepted', 'delivered', 'completed'])) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate($limit)
            ->withQueryString();

        return (new OrderCollection($orders))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function calendar(Request $request)
    {
        $query = auth()->user()->company->orders()->accepted();
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
        $this->validate($request, [
            'status' => 'required|in:pending,in-progress,delivered,completed',
        ], [
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of the following: pending, in-progress, delivered, completed',
        ]);

        if ($order->status == 'pending' && $request->status == 'in-progress') {
            $order->status = $request->status;
            $order->save();
            $message = __('Your order is now in progress.');
        } elseif ($order->status == 'in-progress' && $request->status == 'delivered') {
            $order->status = $request->status;
            $order->save();
            $message = __('Your order is now delivered.');
        } else {
            $order->statusChangeRequest()->create([
                'current_status' => $order->status,
                'new_status' => $request->status,
                'user_id' => auth()->id(),
                'data' => [
                    'item' => [
                        'id' => $order->orderable->id,
                        'type' => $order->orderable_type,
                        'title' => $order->orderable->title ?? $order->orderable->name ?? '',
                    ],
                ],
            ]);
            $message = __('Transaction status change request has been sent successfully, please wait for the user to accept it.');
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