<?php

namespace App\Http\Controllers\Api\v1\Warehouse;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\InventoryCollection;
use App\Http\Resources\v1\Business\InventoryResource;
use App\Http\Resources\v1\Business\WarehouseCollection;
use App\Http\Resources\v1\ReviewCollection;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Inventory;
use App\Models\v1\Offer;
use App\Models\v1\Order;
use App\Models\v1\Transaction;
use App\Models\v1\Wallet;
use App\Notifications\NewServiceOrderRequest;
use App\Notifications\ServiceOrderSuccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 15;
        $query = Inventory::query();

        $services = $query->paginate($limit)->withQueryString()->onEachSide(1);

        return (new InventoryCollection($services))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the services.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @param  string|null  $type
     * @return \Illuminate\Http\Response
     */
    public function companyIndex(Request $request, Company $company, $type = null)
    {
        $limit = $request->limit ?? 15;
        $query = $company->inventories();
        if ($type === 'top') {
            $inventories = $query->limit($limit)->orderByDesc(function ($q) {
                $q->select([DB::raw('count(reviews.id) oc from reviews')])
                    ->where([['reviewable_type', Inventory::class], ['reviewable_id', DB::raw('inventories.id')]]);
            })->orderByDesc(function ($q) {
                $q->select([DB::raw('count(orders.id) oc from orders')])
                    ->where([['orderable_type', Inventory::class], ['orderable_id', DB::raw('inventories.id')]]);
            })->get();
        } elseif ($type === 'most-ordered') {
            $inventories = $query->limit($limit)->orderByDesc(function ($q) {
                $q->select([DB::raw('count(orders.id) oc from orders')])
                    ->where([['orderable_type', Inventory::class], ['orderable_id', DB::raw('inventories.id')]]);
            })->get();
        } elseif ($type === 'top-reviewed') {
            $inventories = $query->limit($limit)->orderByDesc(function ($q) {
                $q->select([DB::raw('count(reviews.id) oc from reviews')])
                    ->where([['reviewable_type', Inventory::class], ['reviewable_id', DB::raw('inventories.id')]]);
            })->get();
        } else {
            $inventories = $query->paginate($limit);
        }

        return (new InventoryCollection($inventories))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the companies for the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Category  $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function category(Request $request, Category $category)
    {
        $limit = $request->get('limit', 15);

        $query = $category->inventories();

        if ($request->paginate === 'cursor') {
            $inventories = $query->cursorPaginate($limit);
        } else {
            $inventories = $query->paginate($limit);
        }

        $response = new InventoryCollection($inventories);

        return $response->additional([
            'category' => $category,
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the inventory.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reviews(Inventory $inventory)
    {
        return (new ReviewCollection($inventory->reviews()->with('user')->paginate()))->additional([
            'message' => 'OK',
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
    public function show(Request $request, Inventory $inventory)
    {
        return (new InventoryResource($inventory))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Process Checkout.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function checkout(Request $request)
    {
        $ref = time().'-OK'.rand(10, 99);

        $orderTransaction = collect($request->items)->map(function ($item) use ($ref, $request) {
            $service = Inventory::findOrFail($item['service_id']);
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
        $items = $orderTransaction;

        Transaction::insert($orderTransaction->map(
            fn ($tr) => collect($tr)
                        ->except(['code', 'orderable_id', 'orderable_type', 'destination', 'company_id']))
                        ->toArray());

        $orderTransaction->map(function ($tr) use ($request) {
            $order = Order::create($tr
                        ->merge(['due_date' => $request->due_date])->toArray());
            $order->company->notify(new NewServiceOrderRequest($order));
            $order->company->user->notify(new ServiceOrderSuccess($order));
            $order->user->notify(new ServiceOrderSuccess($order));
        });

        if ($request->method === 'wallet') {
            $wallet = [
                'user_id' => Auth::id(),
                'amount' => $orderTransaction->sum('amount'),
                'source' => 'Service Orders',
                'detail' => trans_choice('Payment for order of :0 service', $items->count(), [$items->count()]),
                'type' => 'debit',
                'reference' => config('settings.trx_prefix').time().$ref,
            ];
            Wallet::create($wallet);
        }

        return $this->buildResponse([
            'data' => $items,
            'message' => trans_choice('Your orders request for :0 service has been sent successfully, you will be notified when you get a response', $items->count(), [$items->count()]),
            'status' => 'success',
            'refresh' => ['user' => new UserResource(Auth::user())],
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Review the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function review(Request $request, $id)
    {
        //
    }
}