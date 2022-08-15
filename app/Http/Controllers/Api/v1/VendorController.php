<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $app_data = [
            'page' => 'vendor.store',
            'title' => 'Vendor Store',
            'vendor_page' => 'shop',
            'count_items' => Auth::user()->markets()->count(),
            'market_items' => Auth::user()->markets()->paginate(15),
            'categories' => Auth::user()->role === 'vendor'
                ? Category::where('type', 'warehouse')->orderBy('priority', 'ASC')->get()
                : Category::where('type', 'market')->orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.shop', $app_data);
    }

    public function create(Request $request, Market $item)
    {
        $app_data = [
            'page' => 'vendor.store',
            'title' => 'Add Service',
            'vendor_page' => 'create',
            'count_items' => Auth::user()->markets()->count(),
            'market_items' => Auth::user()->markets()->paginate(15),
            'types' => Category::groupBy('type')->get('type')->map(fn ($v) =>$v->type),
            'categories' => Auth::user()->role === 'admin'
                ? Category::orderBy('priority', 'ASC')->get()
                : (Auth::user()->role === 'vendor'
                    ? Category::where('type', 'warehouse')->orderBy('priority', 'ASC')->get()
                    : Category::where('type', 'market')->orderBy('priority', 'ASC')->get()
                ),
        ];

        if ($item->exists()) {
            $app_data['item'] = $item;
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'category_id' => ['required', 'numeric'],
                'title' => ['required', 'string', 'min:3'],
                'price' => ['required', 'numeric'],
                'desc' => ['required', 'string', 'min:3', 'max:200'],
                'details' => ['required', 'string', 'min:3'],
            ]);

            $market = $item->exists() ? $item : new Market;
            $market->user_id = Auth::id();
            $market->category_id = $request->category_id;
            $market->title = $request->title;
            $market->basic = $request->basic;
            $market->type = $request->type;
            $market->price = $request->price;
            $market->desc = $request->desc;
            $market->details = $request->details;

            if ($image = $request->file('image')) {
                Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$image);

                $market->image = \Str::of($image->storeAs(
                    'public/uploads', $image->hashName()
                ))->replace('public/', '');
            }
            $market->save();

            return redirect()->route('vendor.create', [$market->id])->with([
                'message' => [
                    'msg' => $item->exists() ? 'Item Updated.' : 'Item Created.',
                    'type' => 'success',
                ],
            ]);
        }

        $app_data['appData'] = collect($app_data);

        return view('market.create', $app_data);
    }

    public function offers(Request $request, $action = 'list', Offer $offer = null)
    {
        $markets = Auth::user()->markets();
        $mids = $markets->get('id')->map(fn ($v, $k) => $v['id'])->toArray();
        $app_data = [
            'page' => 'vendor.store',
            'title' => 'Offers',
            'vendor_page' => 'offers',
            'markets' => $markets->get(),
            'count_items' => Offer::whereIn('service_id', $mids)->count(),
            'offer_items' => Offer::whereIn('service_id', $mids)->paginate(15),
        ];

        $view = '';

        if ($action === 'create') {
            $view = '-create';
            $app_data['item'] = $offer;
            $app_data['title'] = 'Create Offer';

            if ($request->isMethod('post')) {
                $request->validate([
                    'service_id' => ['required', 'numeric', Rule::in($mids)],
                    'title' => ['required', 'string', 'min:3'],
                    'amount' => ['required', 'numeric'],
                    'desc' => ['required', 'string', 'min:3', 'max:200'],
                ], [], [
                    'service_id' => 'Store Item',
                    'desc' => 'Description',
                ]);

                $new = $offer->exists() ? $offer : new Market;
                $new->service_id = $request->service_id;
                $new->title = $request->title;
                $new->desc = $request->desc;
                $new->type = $request->type;
                $new->operator = $request->operator;
                $new->featured = $request->featured;
                $new->amount = $request->amount;
                $new->save();

                return redirect()->route('vendor.offers', ['create', $new->id])->with([
                    'message' => [
                        'msg' => $offer->exists() ? 'Offer Updated.' : 'Offer Created.',
                        'type' => 'success',
                    ],
                ]);
            }
        }

        $app_data['appData'] = collect($app_data);

        return view('market.vendor-offers'.$view, $app_data);
    }
}
