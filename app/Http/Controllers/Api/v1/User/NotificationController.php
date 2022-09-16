<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\NotificationCollection;
use App\Http\Resources\v1\User\NotificationResource;
use App\Models\v1\Order;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function company(Request $request, $notification = null)
    {
        if ($request->has('unread')) {
            $query = $notification ?? auth()->user()->company->unreadNotifications();
        } else {
            $query = $notification ?? auth()->user()->company->notifications();
        }

        $notifications = $query
            ->paginate($request->get('limit', 15))
            ->withQueryString();

        // Push new notifications if total filtered is less than request limit
        $notifications = $this->padNotifications($request, $notifications, $notification);

        // Exclude notifications where the order request has already been accepted or rejected
        $notifications = $this->padOrders($request, $notifications);

        // Push new notifications if total filtered is less than request limit
        $notifications = $this->padNotifications($request, $notifications, $notification);

        // Exclude notifications where the order request has already been accepted or rejected
        $notifications = $this->padOrders($request, $notifications);

        return (new NotificationCollection($notifications))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    protected function padNotifications(Request $request, $notifications, $notification)
    {
        // Push new notifications if total filtered is less than request limit
        if ($notifications->count() > 0 && $notifications->count() < $request->get('limit', 15)) {
            if ($request->has('unread')) {
                $newQuery = ($notification ?? auth()->user()->company->unreadNotifications())
                    ->where('created_at', '>', $notifications->last()->created_at);
            } else {
                $newQuery = ($notification ?? auth()->user()->company->notifications())
                    ->where('created_at', '>', $notifications->last()->created_at);
            }
            $newNotifications = $newQuery->paginate($request->get('limit', 15) - $notifications->count())
                ->withQueryString();
            $notifications = $notifications->merge($newNotifications);
        }

        return $notifications;
    }

    protected function padOrders(Request $request, $notifications)
    {
        // Exclude notifications where the order request has already been accepted or rejected
        return $notifications->map(function ($notification) {
            if (!$notification->order && ($notification->data['type']??'') === 'service_order') {
                $notification->order = Order::find($notification->data['service_order']['id']);
            }
            return  $notification;
        })->filter(function ($notification) use ($request) {
            if (($notification->data['type']??'') === 'service_order') {
                if ($request->has('accepted')) {
                    return $notification->order && $notification->order->accepted === true && $notification->order->status !== 'rejected';
                } else if ($request->has('rejected')) {
                    return $notification->order && $notification->order->accepted === false && $notification->order->status === 'rejected';
                }
            }
            return true;
        });
    }

    public function account(Request $request)
    {
        if ($request->has('unread')) {
            $notification = auth()->user()->unreadNotifications();
        } else {
            $notification = auth()->user()->notifications();
        }

        return $this->company($request, $notification);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function markAsRead(Request $request, $id)
    {
        if ($request->get('type') === 'company') {
            $notification = auth()->user()->company->unreadNotifications()->findOrFail($id);
        } else {
            $notification = auth()->user()->unreadNotifications();
        }

        $notification->markAsRead();

        if (!$notification->order && ($notification->data['type']??'') === 'service_order') {
            $notification->order = Order::find($notification->data['service_order']['id']);
        }

        return (new NotificationResource($notification))->additional([
            'message' => 'Marked as read',
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
    public function change(Request $request, $notification, $action)
    {
        !in_array($action, ['accept', 'reject']) && abort(404);

        $notification = auth()->user()->company->unreadNotifications()->findOrFail($notification);

        $notification->markAsRead();

        if (!$notification->order && ($notification->data['type']??'') === 'service_order') {
            $notification->order = Order::find($notification->data['service_order']['id']);
        }
        $order = $notification->order;

        if ($order && $action === 'accept') {
            $order->status = 'pending';
            $order->accepted = true;
            $order->save();
        } elseif ($order && $action === 'reject') {
            $order->status = 'rejected';
            $order->accepted = false;
            $order->save();
        }

        return (new NotificationResource($notification))->additional([
            'message' => "Request {$action}ed",
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
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
    public function update(Request $request, $id)
    {
        //
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
