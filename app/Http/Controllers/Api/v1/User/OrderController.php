<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\Inventory;
use App\Models\v1\Order;
use App\Models\v1\Service;
use App\Models\v1\StatusChangeRequests;
use App\Models\v1\User;
use App\Notifications\OrderIsBeingDisputed;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lexx\ChatMessenger\Models\Message;
use Lexx\ChatMessenger\Models\Thread;

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
        $query = Auth()->user()->orders()->cancelled(false)->orderByDesc('id');

        if ($request->has('status') && in_array($request->status, ['pending', 'accepted', 'in-progress', 'delivered', 'completed'])) {
            $query->whereStatus($request->status);
        } elseif ($request->has('status') && $request->status == 'reviewable') {
            $query->whereStatus('completed');
            $query->whereDoesntHave('orderable', function ($query) {
                $query->whereHas('reviews', function ($query) {
                    $query->where('user_id', Auth()->user()->id);
                });
            });
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
     * @param  App\Models\v1\StatusChangeRequests  $order
     * @return \Illuminate\Http\Response
     */
    public function dispute(Request $request, $id)
    {
        $order = $request->isOrder ? Order::findOrFail($id) : StatusChangeRequests::findOrFail($id);

        if ($request->isOrder) {
            $orderRequest = StatusChangeRequests::firstOrNew([
                'status_changeable_id' => $order->id,
                'status_changeable_type' => Order::class,
                'user_id' => Auth()->user()->id,
                'status' => 'disputed',
            ]);
            $orderRequest->current_status = $order->status;
            $orderRequest->new_status = $order->status;
            $alreadyDisputed = $orderRequest->isClean();
            $orderRequest->save();
        } else {
            $orderRequest = $order;
            $order = $orderRequest->status_changeable;
            $alreadyDisputed = $orderRequest->status === 'disputed';
        }

        $product = $order->orderable;
        $company = $order->orderable->company;
        $company_user = $company->user ?? User::whereCompanyId($company->id)->first();

        if ($alreadyDisputed) {
            return $this->buildResponse([
                'message' => 'You have already opened a dispute for this order.',
                'status' => 'error',
                'status_code' => HttpStatus::TOO_MANY_REQUESTS,
            ], HttpStatus::TOO_MANY_REQUESTS);
        }

        $this->validate($request, [
            'reason' => 'required|string|min:20',
        ], [
            'reason.required' => 'Reason is required',
            'reason.min' => 'Reason must be at least 20 characters',
        ]);

        $orderRequest->status = 'disputed';
        $orderRequest->reason = $request->reason;
        $orderRequest->save();

        // Create a support tick
        $admin = User::whereRole('admin')->inRandomOrder()->first();

        $parties = [Auth::id(), $product->user_id, $company->user_id, $company_user->id ?? null, $admin->id];
        if ($request->has('from') && $request->from === 'provider') {
            $parties[] = $order->user_id;
        }
        $parties = collect($parties)
            ->filter(fn ($id) => (bool) $id)
            ->unique();

        $thread = Thread::between($parties->toArray())->where('type', 'dispute')->first();
        if (! $thread) {
            $thread = Thread::create([
                'subject' => 'Order dispute',
                'data' => [
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'order_status_change_request_id' => $orderRequest->id,
                    'order_status_change_request_status' => $orderRequest->status,
                    'order_status_change_request_reason' => $orderRequest->reason,
                ],
            ]);
            $thread->type = 'dispute';
            $thread->save();

            $parties->each(function ($user_id) use ($thread) {
                if (! $thread->hasParticipant($user_id)) {
                    $thread->addParticipant($user_id);
                }
            });
        }

        $product_name = $product->name ?? $product->title;
        $product_code = $product->code ?? $product->id;
        // Message
        $message = Message::insert([[
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => "Item: {$product_name} ({$product_code})",
            'created_at' => now(),
        ], [
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => "Company: {$company->name} ({$company->email})",
            'created_at' => now(),
        ], [
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => 'Reason for dispute: '.$request->input('reason'),
            'created_at' => now(),
        ]]);

        $order->user->notify(new OrderIsBeingDisputed($order));
        $order->orderable->user->notify(new OrderIsBeingDisputed($order));

        return (new OrderResource($order))->additional([
            'message' => __('Your dispute has been logged, a support personel will reachout to you shortly'),
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\v1\StatusChangeRequests  $order
     * @return \Illuminate\Http\Response
     */
    public function updateStatusRequest(Request $request, StatusChangeRequests $order)
    {
        $this->authorize('be-owner', [$order]);

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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        // $this->authorize('be-owner', [$order]);

        $sending_request = false;
        $new_status = null;

        $this->validate($request, [
            'status' => 'required|in:pending,in-progress,delivered,completed,cancelled',
        ], [
            'status.required' => 'Status is required',
            // 'status.in' => 'Status must be one of the following: pending, in-progress, delivered, completed, cancelled',
            'status.in' => 'Status is invalid',
        ]);

        if ($request->status == 'cancelled') {
            $message = __('Your order has been cancelled successfully, your refund is now being processed.');
        } elseif ($order->status == 'pending' && $request->status == 'in-progress') {
            $message = __('Your order is now in progress.');
        } elseif ($order->status == 'in-progress' && $request->status == 'delivered') {
            $message = __('Your order is now delivered.');
        } elseif ($order->status == 'delivered' && $request->status == 'completed' && $order->orderable_type == Service::class) {
            $reviewed = $order->orderable->whereHas('reviews', function ($q) use ($order) {
                $q->whereUserId($order->user_id);
            })->exists();

            $reviewed_msg = $reviewed
                ? __('thank you')
                : __('please take your time to rate this :0', [
                    __('service provider'),
                ]);

            $message = __('Your order is now completed, :0.', [
                $reviewed_msg,
            ]);
        } else {
            $sending_request = true;
            $new_status = $request->status;
            $order->statusChangeRequest()->firstOrCreate([
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
                [$order->orderable_type == Service::class ? __('service provider') : __('warehouse vendor')]
            );
        }

        if (! $sending_request) {
            $order->status = $request->status;
            $order->save();
        }

        $order->user->notify(new OrderStatusChanged($order, $new_status));
        $order->orderable->user->notify(new OrderStatusChanged($order, $new_status));

        return (new OrderResource($order))->additional([
            'message' => $message,
            'status' => 'success',
            'requesting' => $sending_request,
            'status_code' => HttpStatus::OK,
        ]);
    }
}