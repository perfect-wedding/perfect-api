<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $app_data = [
            'page' => 'vendor.shop',
            'title' => 'Vendor Store',
            'vendor_page' => 'shop',
            'count_items' => Auth::user()->markets()->count(),
            'market_items' => Auth::user()->markets()->paginate(15),
            'categories' => Category::orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.index', $app_data);
    }
}
