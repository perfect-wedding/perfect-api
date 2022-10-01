<?php

namespace App\Http\Controllers\Api\v1\Warehouse;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderRequestCollection;
use App\Http\Resources\v1\Provider\OrderRequestResource;
use App\Models\v1\Inventory;
use App\Models\v1\OrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderRequestController extends Controller
{
    /**
     * Display a listing of user's order requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $status
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $status = 'all')
    {
        $user = Auth::user();
        $orders = $user->company->orderRequests()->latest();

        if (in_array($status, ['pending', 'accepted', 'rejected'])) {
            $orders->{$status}();
        }

        $orderRequests = $orders
            ->paginate($request->get('limit', 15))
            ->withQueryString();

        return (new OrderRequestCollection($orderRequests))->additional([
            'message' => $orderRequests->isEmpty() ? 'No order request available' : HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    public function update($id, $status = 'all')
    {
        $user = Auth::user();
        $orderRequest = $user->company->orderRequests()->findOrFail($id);

        if (in_array($status, ['accepted', 'rejected'])) {
            $orderRequest->{$status} = true;
            $orderRequest->save();
        }

        return (new OrderRequestResource($orderRequest))->additional([
            'message' => __('Order request :0', [$status]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Process Checkout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendRequest(Request $request)
    {
        $ref = time().'-OK'.rand(10, 99);
        $inventory = Inventory::findOrFail($request->item_id);
        // $package = $request->package_id == '0'
        //     ? Offer::where('id', 0)->firstOrNew()
        //     : Offer::findOrFail($request->package_id);

        if ($inventory->orderRequests()->whereUserId(auth()->id())->pending()->count() > 0) {
            return response()->json([
                'message' => 'You have already sent a request for this item',
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ], HttpStatus::BAD_REQUEST);
        }

        $order_request = new OrderRequest;
        $order_request->orderable()->associate($inventory);
        $order_request->code = $ref;
        $order_request->user_id = auth()->id();
        $order_request->company_id = $inventory->company_id;
        $order_request->package_id = $request->package_id;
        $order_request->amount = $inventory->offerCalculator($request->package_id);
        $order_request->qty = $request->qty ?? 1;
        $order_request->destination = $request->destination ? (
            collect([
                $request->address,
                $request->city,
                $request->state,
                $request->country,
            ])->filter(fn ($i) => (bool) $i)->implode(', ')
        ) : Auth::user()->address;
        $order_request->due_date = $request->due_date;
        // $order->company->notify(new NewServiceOrderRequest($order));
        // $order->company->user->notify(new ServiceOrderSuccess($order));
        // $order->user->notify(new ServiceOrderSuccess($order));

        $order_request->save();

        return (new OrderRequestResource($order_request))->additional([
            'message' => __('Your order request for :0 has been sent successfully, you will be notified when you get a response.', [$inventory->name]),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
