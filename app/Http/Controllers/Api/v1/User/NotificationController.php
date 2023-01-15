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
    public function company(Request $request, $notifications = null, $unread = null)
    {
        if ($request->has('unread')) {
            $query = $notifications ?? auth()->user()->company->unreadNotifications();
        } else {
            $query = $notifications ?? auth()->user()->company->notifications();
        }

        $notifications = $query
            ->paginate($request->get('limit', 15))
            ->withQueryString();

        $unread = $unread ?? auth()->user()->company->unreadNotifications()->count();

        return (new NotificationCollection($notifications))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'unread' => $unread,
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function account(Request $request)
    {
        if ($request->has('unread')) {
            $notifications = auth()->user()->unreadNotifications();
        } else {
            $notifications = auth()->user()->notifications();
        }

        $unread = $unread ?? auth()->user()->unreadNotifications()->count();

        return $this->company($request, $notifications, $unread);
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
            $notification = auth()->user()->company->unreadNotifications();
        } else {
            $notification = auth()->user()->unreadNotifications();
        }

        if ($request->has('items') && $id === 'multiple') {
            $count = $notification->whereIn('id', $request->get('items'))->update(['read_at' => now()]);
        } else {
            $count = $notification->where('id', $id)->update(['read_at' => now()]);
        }

        $unread = $request->get('type') === 'company'
            ? auth()->user()->company->unreadNotifications()->count()
            : auth()->user()->unreadNotifications()->count();

        $additional = [
            'data' => $id !== 'multiple' ? new NotificationResource(auth()->user()->notifications()->find($id)) : null,
            'message' => __(':0 Notifications marked as read.', [$count]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ];

        return $this->buildResponse($additional, ['unread' => $unread ?? 0]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function change(Request $request, $notification, $action)
    {
        ! in_array($action, ['accept', 'reject']) && abort(404);

        $notification = auth()->user()->company->unreadNotifications()->findOrFail($notification);

        $notification->markAsRead();

        if (! $notification->order && ($notification->data['type'] ?? '') === 'service_order') {
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
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if ($request->get('type') === 'company') {
            $notifications = auth()->user()->company->notifications();
        } else {
            $notifications = auth()->user()->notifications();
        }

        $notification = $notifications->findOrFail($id);

        $notification->delete();

        return $this->buildResponse([
            'message' => __('Notification deleted.'),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
