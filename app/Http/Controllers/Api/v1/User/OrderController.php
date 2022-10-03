<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Provider\OrderCollection;
use App\Http\Resources\v1\Provider\OrderResource;
use App\Models\v1\StatusChangeRequests;
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
