<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\Order;
use App\Models\v1\StatusChangeRequests;
use App\Models\v1\User;
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
        $query = Auth()->user()->orders()->orderByDesc('id');

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

        $order->orderable->reviews()->create([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'user_id' => Auth()->user()->id,
        ]);

        return response()->json([
            'message' => __('Your review has been submitted successfully, thnak you.'),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\v1\StatusChangeRequests  $order
     * @return \Illuminate\Http\Response
     */
    public function dispute(Request $request, StatusChangeRequests $order)
    {
        $item = $order;
        $order = $item->status_changeable;
        $product = $order->orderable;
        $company = $order->orderable->company;
        $company_user = User::whereCompanyId($company->id)->first();

        if ($item->status === 'disputed') {
            return response()->json([
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

        $item->status = 'disputed';
        $item->reason = $request->reason;
        $item->save();

        // Create a support tick
        $admin = User::whereRole('admin')->inRandomOrder()->first();

        $parties = [Auth::id(), $product->user_id, $company->user_id, $company_user->id ?? null, $admin->id];
        if ($request->has('from') && $request->from === 'provider') {
            $parties[] = $order->user_id;
        }
        $parties = collect($parties)
            ->filter(fn ($id) => (bool) $id)
            ->unique();

        $thread = Thread::between($parties->toArray())->first();
        if (! $thread) {
            $thread = Thread::create([
                'subject' => 'Order dispute',
                'data' => [
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'order_status_change_request_id' => $item->id,
                    'order_status_change_request_status' => $item->status,
                    'order_status_change_request_reason' => $item->reason,
                ],
            ]);

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

        return (new OrderResource($order))->additional([
            'message' => __('Your dispute has been logged, a support personel will reachout to you shortly'),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
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
        if ($order->user_id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot update your own status change request.',
                'status' => 'error',
                'status_code' => HttpStatus::UNAUTHORIZED,
            ], HttpStatus::UNAUTHORIZED);
        }

        $item = $order;
        $order = $item->status_changeable;

        $this->validate($request, [
            'status' => 'required|in:accept,reject',
        ], [
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of the following: accept, reject',
        ]);

        if ($request->status == 'accept') {
            $order->status = $item->new_status;
            $order->save();
            $item->delete();
        } else {
            $item->delete();
        }

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
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.',
                'status' => 'error',
                'status_code' => HttpStatus::UNAUTHORIZED,
            ], HttpStatus::UNAUTHORIZED);
        }

        $sending_request = false;
        $company_type = $order->orderable->company->type;

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
        } elseif ($order->status == 'delivered' && $request->status == 'completed' && $company_type == 'provider') {
            $order->status = $request->status;
            $order->save();
            $message = __('Your order is now completed, please take your time to rate this :0.', [
                str(str($order->orderable_type)->explode('\\')->last())->lower()->singular()->title(),
            ]);
        } else {
            $sending_request = true;
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
                [$company_type]
            );
        }

        return (new OrderResource($order))->additional([
            'message' => $message,
            'status' => 'success',
            'requesting' => $sending_request,
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
