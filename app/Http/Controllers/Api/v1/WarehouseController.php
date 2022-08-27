<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Market;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        if ($request['filter.price-range']) {
            $markets = Market::whereBetween('price', rangable($request['filter.price-range'], '-', '0'));
        } else {
            $markets = Market::query();
        }
        if ($request['filter.brands']) {
            $category_ids = Category::whereIn('slug', explode(',', $request['filter.brands']))->get('id')->map(fn ($v) => $v->id);
            $markets->whereIn('category_id', $category_ids);
        }
        $markets->where('type', 'warehouse');

        $app_data = [
            'page' => 'warehouse.index',
            'title' => 'Warehouse',
            'cur_category' => 'all',
            'count_items' => $markets->count(),
            'market_items' => $markets->paginate(15),
            'categories' => Category::where('type', 'warehouse')->orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.index', $app_data);
    }

    public function category(Request $request, Category $category)
    {
        if ($request['filter.price-range']) {
            $markets = $category->markets()->whereBetween('price', rangable($request['filter.price-range'], '-', '0'));
        } else {
            $markets = $category->markets();
        }
        if ($request['filter.brands']) {
            $category_ids = Category::whereIn('slug', explode(',', $request['filter.brands']))->get('id')->map(fn ($v) => $v->id);
            $markets->whereIn('category_id', $category_ids);
        }
        $markets->where('type', 'warehouse');

        $app_data = [
            'page' => 'warehouse.index',
            'title' => 'Warehouse Categories',
            'cur_category' => $category->slug,
            'count_all' => $markets->count(),
            'count_items' => $markets->count(),
            'market_items' => $markets->paginate(15),
            'categories' => Category::where('type', 'warehouse')->orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.index', $app_data);
    }
}
