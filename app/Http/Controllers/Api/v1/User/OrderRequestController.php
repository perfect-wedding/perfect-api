<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderRequestCollection;
use App\Http\Resources\v1\Provider\OrderRequestResource;
use App\Models\v1\OrderRequest;
use App\Models\v1\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderRequestController extends Controller
{
    /**
     * Display a listing of user's order requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $status
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $status = 'all')
    {
        $user = Auth::user();
        $orders = $user->orderRequests()->latest();

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
        ]);
    }

    /**
     * Display a listing of user's order requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $status
     * @return \Illuminate\Http\Response
     */
    public function check(Service $service, $status = 'all')
    {
        if ($service) {
            $order_request = $service->orderRequests()->whereUserId(Auth::id());
            if ($order_request) {
                if (in_array($status, ['pending', 'accepted', 'rejected'])) {
                    $order_request->{$status}();
                } elseif ($status == 'pending-accepted') {
                    $order_request->available();
                }

                $status_name = $status != 'all' ? str_ireplace('-', '/', $status.' ') : '';
                $count_items = $order_request->count();
                $response = [
                    'message' => __('You have :0 :1order request for this service', [$count_items, $status_name]),
                    'count' => $count_items,
                    'status' => 'success',
                    'status_code' => HttpStatus::OK,
                ];
                if ($count_items < 1) {
                    return $this->buildResponse(collect($response)->except(['count']), ['count' => 0]);
                }
                $item = $order_request->first();
                if ($item->accepted) {
                    $response['accepted'] = true;
                }

                return (new OrderRequestResource($item))->additional($response);
            }
        }

        abort(HttpStatus::NOT_FOUND);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderRequest $order_request)
    {
        $order_request->delete();

        return $this->buildResponse([
            'message' => "Order request for \"{$order_request->orderable->title}\" canceled.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}