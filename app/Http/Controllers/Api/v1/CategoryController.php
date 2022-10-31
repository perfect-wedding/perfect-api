<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CategoryCollection;
use App\Http\Resources\v1\Business\CompanyCollection;
use App\Http\Resources\v1\Business\WarehouseCollection;
use App\Models\v1\Category;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use App\Models\v1\ShopItem;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $count = Category::select(DB::raw('categories.id cid, (select count(id) from services where category_id = cid) as cs'))
        //  ->get('cs')->sum('cs');
        $query = Category::orderBy('priority')->orderBy('created_at');

        $countQuery = ($request->type === 'warehouse'
            ? Inventory::query()->ownerVerified()
            : ($request->type === 'giftshop'
                ? ShopItem::query()->shopActive()
                : Service::query()->ownerVerified()
            )
        );

        if ($request->has('type')) {
            $query->where('type', $request->type);

            if ($request->has('company')) {
                $company = $request->company;
                $query->forCompany($company, $request->type);
                $countQuery->whereHas('company', function ($q) use ($company) {
                    $q->where('id', $company);
                    $q->orWhere('slug', $company);
                });
            }
        }

        if ($request->has('gender') && $request->type === 'giftshop') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('shop_items', function ($query) use ($request) {
                    $query->where('gender', $request->gender);
                    $query->orWhere('gender', null);
                    $query->orWhere('gender', '');
                }); //->orWhereDoesntHave('shop_items');
            });
            $countQuery->where(function ($query) use ($request) {
                $query->where('gender', $request->gender);
                $query->orWhere('gender', null);
                $query->orWhere('gender', '');
            });
        }

        if ($request->paginate === 'cursor') {
            $categories = $query->cursorPaginate($request->get('limit', 15))->withQueryString();
        } else {
            $categories = $query->paginate($request->get('limit', 15))->withQueryString();
        }

        return (new CategoryCollection($categories))->additional([
            'message' => 'OK',
            'status' => 'success',
            'total_services' => $countQuery->count(),
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
    public function show(Request $request, Category $category)
    {
        $limit = $request->get('limit', 15);

        $query = $category->type === 'warehouse'
            ? $category->warehouse_companies->verified()
            : $category->companies->verified();

        if ($request->paginate === 'cursor') {
            $companies = $query->cursorPaginate($limit);
        } else {
            $companies = $query->paginate($limit);
        }

        if ($category->type === 'warehouse') {
            $response = new WarehouseCollection($companies);
        } else {
            $response = new CompanyCollection($companies);
        }

        return $response->additional([
            'category' => $category,
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
