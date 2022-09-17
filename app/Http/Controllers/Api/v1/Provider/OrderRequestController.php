<?php

namespace App\Http\Controllers\Api\v1\Provider;

use App\EnumsAndConsts\HttpStatus;
use Illuminate\Http\Request;

class OrderRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = auth()->user()->company->orders()->accepted();
        $orders = $query
            ->paginate($request->get('limit', 15))
            ->withQueryString();

        return (new OrderCollection($orders))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Process Checkout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function request(Request $request)
    {
        $ref = time().'-OK'.rand(10, 99);

        $transactionRequest = collect($request->items)->map(function ($item) use ($ref, $request) {
            $service = Service::findOrFail($item['service_id']);
            $package = $item['package_id'] == '0' ? Offer::where('id', 0)->firstOrNew() : Offer::findOrFail($item['package_id']);
            $item['user_id'] = Auth::id();
            $item['orderable_id'] = $item['transactable_id'] = $service->id;
            $item['orderable_type'] = $item['transactable_type'] = get_class($service);
            $item['company_id'] = $service->company_id;
            $item['destination'] = $request->location ? (
                collect([
                    $request->location['address'] ?? null,
                    $request->location['city'] ?? null,
                    $request->location['state'] ?? null,
                    $request->location['country'] ?? null,
                ])->filter(fn ($i) => $i !== null)->implode(', ')
            ) : Auth::user()->address;
            $item['status'] = 'pending';
            $item['method'] = request('method');
            $item['code'] = $item['reference'] = 'ODR-'.$ref;
            $item['amount'] = $service->offerCalculator($item['package_id']);
            $item['due'] = $service->price;
            $item['offer_charge'] = $service->packAmount($item['package_id']);
            $item['discount'] = $package->type === 'discount' ? (($dis = $service->price - $item['amount']) > 0 ? $dis : 0.00) : 0.00;
            $item['created_at'] = Carbon::now();
            $item['updated_at'] = Carbon::now();

            return collect($item)->except(['route', 'image', 'title', 'book_date', 'type', 'package_id', 'service_id', 'location']);
        });
        $items = $transactionRequest;

        Transaction::insert($transactionRequest->map(
            fn ($tr) => collect($tr)
                        ->except(['code', 'orderable_id', 'orderable_type', 'destination', 'company_id']))
                        ->toArray());

        $transactionRequest->map(function ($tr) use ($request) {
            $order = Order::create($tr
                        ->merge(['due_date' => $request->due_date])->toArray());
            // $order->company->notify(new NewServiceOrderRequest($order));
            // $order->company->user->notify(new ServiceOrderSuccess($order));
            // $order->user->notify(new ServiceOrderSuccess($order));
        });

        return $this->buildResponse([
            'data' => $items,
            'message' => trans_choice('Your order request for :0 service has been sent successfully, you will be notified when you get a response', $items->count(), [$items->count()]),
            'status' => 'success',
            'refresh' => ['user' => new UserResource(Auth::user())],
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}

