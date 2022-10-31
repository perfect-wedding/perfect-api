<?php

namespace App\Http\Controllers\Api\v1\Warehouse;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\InventoryCollection;
use App\Http\Resources\v1\Business\InventoryResource;
use App\Http\Resources\v1\ReviewCollection;
use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of all inventory items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cats = explode(',', $request->get('categories', ''));
        if ($request->has('categories') && ! empty($cats[0])) {
            $query = Inventory::whereHas('category', function ($category) use ($cats) {
                $category->whereIn('slug', $cats)
                        ->orWhereIn('id', $cats)
                        ->ownerVerified();
            });
        } elseif ($request->has('category')) {
            $category = Category::where('slug', $request->get('category'))
                                ->orWhere('id', $request->get('category'))
                                ->ownerVerified()->firstOrFail();
            $query = $category->inventories();
        } else {
            $query = Inventory::query();
        }

        if ($request->has('price_range')) {
            $query->whereBetween('price', rangeable($request->input('price_range')));
        }

        if ($request->has('ratings')) {
            // Filter Items by their average review ratings
            $query->whereHas('reviews', function ($review) use ($request) {
                $review->selectRaw('avg(rating) as average_rating')
                        ->groupBy('reviewable_id')
                        ->havingRaw('avg(rating) >= ?', [$request->input('ratings')]);
            });
        }

        if ($request->has('company')) {
            $company = Company::where('slug', $request->get('company'))
                                ->orWhere('id', $request->get('company'))
                                ->verified()->firstOrFail();
            $query->where('company_id', $company->id);
        }

        $inventories = $query->ownerVerified()
                            ->orderingBy()
                            ->paginate($request->input('limit', 15))
                            ->withQueryString()
                            ->onEachSide(1);

        return (new InventoryCollection($inventories))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display a listing of all inventory items for the selected company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @param  string|null  $type
     * @return \Illuminate\Http\Response
     */
    public function companyIndex(Request $request, Company $company, $type = null)
    {
        $limit = $request->input('limit', 15);
        $type  = $request->input('type', $type);

        $query = $company->inventories()->ownerVerified();

        if ($type && in_array($type, ['top', 'most-ordered', 'most-reviewed'])) {
            $inventories = $query->orderingBy($type)->paginate($limit);
        } else {
            $inventories = $query->orderingBy()->paginate($limit);
        }

        return (new InventoryCollection($inventories))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the inventory based on the category selected.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Category  $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function category(Request $request, Category $category)
    {
        $limit = $request->get('limit', 15);

        $query = $category->inventories()->ownerVerified();

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
        $reference = config('settings.trx_prefix', 'TRX-').$this->generate_string(20, 3);
        $user = Auth::user();

        $items = collect($request->items)->map(function ($item) use ($user) {
            $request = $user->orderRequests()->find($item['request_id'] ?? '');
            if (! $request) {
                return null;
            }
            $orderable = Inventory::find($item['item_id']);
            $package = $orderable->offers()->find($item['package_id']) ?? ['id' => 0];
            $quantity = $item['quantity'] ?? 1;
            $total = $orderable->offerCalculator($item['package_id']) * $quantity;
            $transaction = $orderable->transactions();
            $item['due'] = $orderable->price;

            return [
                'package' => $package,
                'quantity' => $quantity,
                'orderable' => $orderable,
                'transaction' => $transaction,
                'request' => $request,
                'total' => $total,
            ];
        })->filter(fn ($item) => $item !== null);
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