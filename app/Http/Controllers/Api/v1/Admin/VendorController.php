<?php

namespace App\Http\Controllers\Admin;

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
            'page' => 'admin.manage.market',
            'title' => 'Business Manager',
            'vendor_page' => 'shop',
            'count_items' => Market::count(),
            'market_items' => Market::paginate(15),
            'categories' => Category::orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('admin.market', $app_data);
    }

    public function create(Request $request, Market $item)
    {
        $app_data = [
            'page' => 'admin.manage.market',
            'title' => 'Business Manager (Add Service)',
            'vendor_page' => 'create',
            'count_items' => Auth::user()->markets()->count(),
            'market_items' => Auth::user()->markets()->paginate(15),
            'categories' => Category::orderBy('priority', 'ASC')->get(),
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
            $market->price = $request->price;
            $market->desc = $request->desc;
            $market->details = $request->details;

            if ($image = $request->file('image')) {
                $market && Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$market->image);

                $market->image = \Str::of($image->storeAs(
                    'public/uploads', $image->hashName()
                ))->replace('public/', '');
            }
            $market->save();

            return redirect()->route('admin.market.create', [$market->id])->with([
                'message' => [
                    'msg' => $item->exists() ? 'Item Updated.' : 'Item Created.',
                    'type' => 'success',
                ],
            ]);
        }

        $app_data['appData'] = collect($app_data);

        return view('admin.market-create', $app_data);
    }

    public function delete(Request $request, Market $item)
    {
        $item && Storage::delete((config('filesystems.default') === 'local' ? 'public/' : '').$item->image);

        $item->offers()->delete();
        $item->delete();

        return back()->with([
            'message' => [
                'msg' => 'Item Deleted.',
                'type' => 'success',
            ],
        ]);
    }

    public function offers(Request $request, $action = 'list', Offer $offer = null)
    {
        $app_data = [
            'page' => 'admin.manage.market',
            'title' => 'Business Manager (Offers)',
            'vendor_page' => 'offers',
            'count_items' => Offer::count(),
            'offer_items' => Offer::paginate(15),
        ];

        $view = '';

        if ($action === 'create' && $offer->id) {
            $view = '-create';
            $app_data['item'] = $offer;
            $app_data['title'] = 'Business Manager (Create Offer)';

            if ($request->isMethod('post')) {
                $request->validate([
                    'service_id' => ['required', 'numeric', Rule::in(Market::get('id')->implode(','))],
                    'title' => ['required', 'string', 'min:3'],
                    'amount' => ['required', 'numeric'],
                    'desc' => ['required', 'string', 'min:3', 'max:200'],
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

                return redirect()->route('admin.market.offers', ['create', $new->id])->with([
                    'message' => [
                        'msg' => $offer->exists() ? 'Offer Updated.' : 'Offer Created.',
                        'type' => 'success',
                    ],
                ]);
            }
        }

        $app_data['appData'] = collect($app_data);

        return view('admin.market-offers'.$view, $app_data);
    }

    public function deleteOffer(Request $request, Offer $offer)
    {
        $offer->delete();

        return back()->with([
            'message' => [
                'msg' => 'Offer Deleted.',
                'type' => 'success',
            ],
        ]);
    }
}
