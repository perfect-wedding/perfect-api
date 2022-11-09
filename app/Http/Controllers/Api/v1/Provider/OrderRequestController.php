<?php

namespace App\Http\Controllers\Api\v1\Provider;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderRequestCollection;
use App\Http\Resources\v1\Provider\OrderRequestResource;
use App\Models\v1\OrderRequest;
use App\Models\v1\Service;
use App\Notifications\NewServiceOrderRequest;
use App\Notifications\ServiceOrderRequestUpdated;
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
            $orderRequest->user->notify(new ServiceOrderRequestUpdated($orderRequest, $status));
            $orderRequest->company->notify(new ServiceOrderRequestUpdated($orderRequest, $status));
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
        $service = Service::findOrFail($request->service_id);
        // $package = $request->package_id == '0'
        //     ? Offer::where('id', 0)->firstOrNew()
        //     : Offer::findOrFail($request->package_id);

        $order_request = new OrderRequest;
        $order_request->orderable()->associate($service);
        $order_request->code = $ref;
        $order_request->user_id = auth()->id();
        $order_request->company_id = $service->company_id;
        $order_request->package_id = $request->package_id;
        $order_request->amount = $service->offerCalculator($request->package_id);
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
        $order_request->user->notify(new NewServiceOrderRequest($order_request));
        $order_request->company->notify(new NewServiceOrderRequest($order_request));

        $order_request->save();

        return (new OrderRequestResource($order_request))->additional([
            'message' => __('Your order request for :0 has been sent successfully, you will be notified when you get a response.', [$service->title]),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}