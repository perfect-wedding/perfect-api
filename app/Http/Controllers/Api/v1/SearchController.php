<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use App\Models\v1\ShopItem;
use Illuminate\Http\Request;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Search;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q', '');
        $search = $request->q
        ? (new Search())
        ->registerModel(Category::class, 'title', 'description')
        ->registerModel(Company::class, function (ModelSearchAspect $modelSearchAspect) {
            $modelSearchAspect
               ->addSearchableAttribute('name')
               ->addSearchableAttribute('address')
               ->addExactSearchableAttribute('country')
               ->addExactSearchableAttribute('state')
               ->addExactSearchableAttribute('city')
               ->addExactSearchableAttribute('type')
               ->verified()
               ->where(function ($query) {
                   $query->whereHas('services', function ($query) {
                       $query->where('id', '!=', null);
                   })->orWhereHas('inventories', function ($query) {
                       $query->where('id', '!=', null);
                   });
               });
        })
        ->registerModel(Service::class, function (ModelSearchAspect $modelSearchAspect) use ($q) {
            $modelSearchAspect
               ->addSearchableAttribute('title')
               ->addSearchableAttribute('basic_info')
               ->addSearchableAttribute('short_desc')
               ->addSearchableAttribute('details')
               ->addSearchableAttribute('price')
               ->addExactSearchableAttribute('type')
               ->orWhereHas('company', function ($query) use ($q) {
                    $query->where(function($query) use ($q) {
                        $query->where('address', 'like', "%$q%")
                            ->orWhere('name', 'like', "%$q%")
                            ->orWhere('city', 'like', "%$q%")
                            ->orWhere('state', 'like', "%$q%")
                            ->orWhere('country', 'like', "%$q%")
                            ->orWhere('type', 'like', "%$q%");
                    });
                })
                ->whereHas('company', function ($query) use ($q) {
                    $query->verified();
                });
        })
        ->registerModel(Inventory::class, function (ModelSearchAspect $modelSearchAspect) use ($q) {
            $modelSearchAspect
               ->addSearchableAttribute('name')
               ->addSearchableAttribute('basic_info')
               ->addSearchableAttribute('details')
               ->addSearchableAttribute('price')
               ->addExactSearchableAttribute('type')
               ->orWhereHas('company', function ($query) use ($q) {
                    $query->where(function($query) use ($q) {
                        $query->where('address', 'like', "%$q%")
                            ->orWhere('name', 'like', "%$q%")
                            ->orWhere('city', 'like', "%$q%")
                            ->orWhere('state', 'like', "%$q%")
                            ->orWhere('country', 'like', "%$q%")
                            ->orWhere('type', 'like', "%$q%");
                    });
                })
                ->whereHas('company', function ($query) use ($q) {
                    $query->verified();
                });
        })
        ->registerModel(ShopItem::class, function (ModelSearchAspect $modelSearchAspect) {
            $modelSearchAspect
               ->addSearchableAttribute('name')
               ->addSearchableAttribute('basic_info')
               ->addSearchableAttribute('details')
               ->addSearchableAttribute('price')
               ->whereHas('shop', function ($query) {
                   $query->active();
               });
        })
        ->limitAspectResults($request->get('limit', 25))
        ->search($request->get('q', '')) : collect([]);

        $results = $search->map(function ($result) {
            // dd($result);
            $item = $result->searchable;

            return [
                'id' => $result->searchable->id,
                'key' => str($result->searchable->id)->append($result->type)->slug(),
                'url' => $result->url,
                'url' => ['company' => $item->company->slug ??  $item->shop->slug ?? null, 'item' => $item->slug],
                'title' => $result->title,
                'type' => $result->type,
                'image' => $item->images['image'] ?? $item->images['banner'] ?? $item->image_url ?? $item->banner_url ?? null,
                'description' => str($item->description ?? $item->about ?? $item->intro ?? $item->details ?? $item->basic_info ?? $item->short_desc ?? '')->words(25)->__toString(),
            ];
        });

        return $this->buildResponse([
            'message' => __("Search for ':q' returned :count results", ['q' => $request->get('q'), 'count' => $results->count()]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
            'results' => $results,
            'total' => $search->count(),
            'query' => $request->get('q'),
        ]);
    }
}