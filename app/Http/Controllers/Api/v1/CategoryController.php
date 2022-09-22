<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CategoryCollection;
use App\Http\Resources\v1\CompanyCollection;
use App\Models\v1\Category;
use App\Models\v1\Service;
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

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->paginate === 'cursor') {
            $categories = $query->cursorPaginate($request->get('limit', 15));
        } else {
            $categories = $query->paginate($request->get('limit', 15));
        }

        return (new CategoryCollection($categories))->additional([
            'message' => 'OK',
            'status' => 'success',
            'total_services' => Service::count(),
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
        $limit = $request->limit ?? 15;
        $query = $category->companies;
        if ($request->paginate === 'cursor') {
            $companies = $query->cursorPaginate($limit);
        } else {
            $companies = $query->paginate($limit);
        }

        return (new CompanyCollection($companies))->additional([
            'category' => $category,
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}