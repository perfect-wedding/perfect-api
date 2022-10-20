<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use Illuminate\Http\Request;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Search;

class SearchController extends Controller
{
    public function index(Request $request)
    {
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
        ->registerModel(Service::class, function (ModelSearchAspect $modelSearchAspect) {
            $modelSearchAspect
               ->addSearchableAttribute('title')
               ->addSearchableAttribute('basic_info')
               ->addSearchableAttribute('short_desc')
               ->addSearchableAttribute('details')
               ->addExactSearchableAttribute('price')
               ->addExactSearchableAttribute('type')
               ->whereHas('company', function ($query) {
                   $query->verified();
               });
        })
        ->registerModel(Inventory::class, function (ModelSearchAspect $modelSearchAspect) {
            $modelSearchAspect
               ->addSearchableAttribute('name')
               ->addSearchableAttribute('basic_info')
               ->addSearchableAttribute('details')
               ->addExactSearchableAttribute('price')
               ->addExactSearchableAttribute('type')
               ->whereHas('company', function ($query) {
                   $query->verified();
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
                'url' => ['company' => $item->company->slug ?? null, 'item' => $item->slug],
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