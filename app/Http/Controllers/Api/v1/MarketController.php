<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\v1\Category;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index(Request $request)
    {
        if ($request['filter.price-range']) {
            $markets = Market::whereBetween('price', rangable($request['filter.price-range'], '-', '0'));
        } else {
            $markets = Market::query();
        }
        if ($request['filter.brands']) {
            $category_ids = Category::whereIn('slug', explode(',', $request['filter.brands']))->get('id')->map(fn ($v) =>$v->id);
            $markets->whereIn('category_id', $category_ids);
        }
        $app_data = [
            'page' => 'market.place',
            'title' => 'Market Place',
            'cur_category' =>'all',
            'count_items' => $markets->count(),
            'market_items' => $markets->where('type', 'market')->paginate(15),
            'categories' => Category::where('type', 'market')->orderBy('priority', 'ASC')->get(),
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
            $category_ids = Category::whereIn('slug', explode(',', $request['filter.brands']))->get('id')->map(fn ($v) =>$v->id);
            $markets->whereIn('category_id', $category_ids);
        }
        $app_data = [
            'page' => 'market.category',
            'title' => 'Market Place Categories',
            'cur_category' => $category->slug,
            'count_all' => $markets->count(),
            'count_items' => $markets->count(),
            'market_items' => $markets->paginate(15),
            'categories' => Category::where('type', 'market')->orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.index', $app_data);
    }

    public function item(Request $request, Market $item)
    {
        $app_data = [
            'page' => (request()->segment(1) === 'explore' && request()->segment(2) === 'warehouse') ? 'warehouse.item' : 'market.item',
            'item' => $item,
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.item', $app_data);
    }
}
