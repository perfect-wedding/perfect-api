<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
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

        if ($request->has('status') && in_array($request->status, ['pending', 'accepted', 'delivered', 'completed'])) {
            $query->{$request->status}();
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

        $parties = collect([Auth::id(), $product->user_id, $company->user_id, $company_user->id ?? null, $admin->id])
            ->filter(fn ($id) => (bool) $id)
            ->unique();

        $thread = Thread::between($parties->toArray())->first();
        if (! $thread) {
            $thread = Thread::create([
                'subject' => 'Order dispute',
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
    public function update(Request $request, StatusChangeRequests $order)
    {
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
