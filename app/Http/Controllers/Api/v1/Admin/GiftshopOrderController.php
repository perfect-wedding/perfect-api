<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\GiftShop;
use App\Models\v1\StatusChangeRequests;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;

class GiftshopOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\GiftShop  $giftshop
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, GiftShop $giftshop)
    {
        $this->authorize('can-do', ['giftshop']);
        $limit = $request->get('limit', 15);
        $query = $giftshop->orders()->cancelled(false)->orderByDesc('id');

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
     * Update the order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\GiftShop  $giftshop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GiftShop $giftshop, $id)
    {
        $order = $giftshop->orders()->findOrFail($id);

        $this->authorize('can-do', ['giftshop']);
        $this->validate($request, [
            'status' => 'required|in:pending,in-progress,delivered,completed',
        ], [
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of the following: pending, in-progress, delivered, completed',
        ]);
        $requesting = false;

        if ($order->status == 'pending' && $request->status == 'in-progress') {
            $message = __('Your order is now in progress.');
        } elseif ($order->status == 'in-progress' && $request->status == 'delivered') {
            $message = __('Your order is now delivered.');
        } else {
            $requesting = true;
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
            $message = __('Transaction status change request has been sent successfully, please wait for the :0 to accept it.',
                ['user']
            );
        }

        if (! $requesting) {
            $order->status = $request->status;
            $order->save();
        }

        return (new OrderResource($order))->additional([
            'message' => $message,
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function review(Request $request, Order $order)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string',
        ]);

        // Check if the reviewer owns the order then review the user instead
        if (in_array(Auth()->user()->id, [$order->orderable->user_id, $order->orderable->company->user_id]) ||
            $order->orderable->company->id == Auth()->user()->company_id) {
            $reviews = $order->user->reviews();
            $thank = $request->done
                ? __('This order has now been completed, thank you for your feedback!')
                : __('Thank you for your feedback!');
        } else {
            $reviews = $order->orderable->reviews();
            $thank = __('Thank you for your feedback!');
        }

        $reviews->create([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'user_id' => Auth()->user()->id,
        ]);

        return $this->buildResponse([
            'message' => __('Your review has been submitted successfully. :0 :1', [
                $order->orderable_type == Inventory::class && $order->user_id == Auth()->user()->id
                    ? __('Your order has been marked as :0 and is now awaiting confirmation.', [$request->done])
                    : '',
                $thank,
            ]),
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\GiftShop  $giftshop
     * @param  App\Models\v1\StatusChangeRequests  $order
     * @return \Illuminate\Http\Response
     */
    public function updateStatusRequest(Request $request, GiftShop $giftshop, StatusChangeRequests $order)
    {
        $this->authorize('be-owner', [$order->status_changeable->orderable]);

        $orderRequest = $order;
        $order = $orderRequest->status_changeable;

        $this->validate($request, [
            'status' => 'required|in:accept,reject',
        ], [
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of the following: accept, reject',
        ]);

        if ($request->status == 'accept') {
            $order->status = $orderRequest->new_status;
            $order->save();
            $orderRequest->delete();
        } else {
            $orderRequest->delete();
        }

        $order->user->notify(new OrderStatusChanged($order));
        $order->orderable->user->notify(new OrderStatusChanged($order));

        return (new OrderResource($order))->additional([
            'message' => __('Request has been :0ed successfully', [$request->status]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
