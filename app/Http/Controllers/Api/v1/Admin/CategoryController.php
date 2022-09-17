<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CategoryCollection;
use App\Http\Resources\v1\CategoryResource;
use App\Models\v1\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $this->authorize('usable', 'content');
        $query = Category::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', "%$request->search%")
                      ->orWhere('type', "%$request->search%")
                      ->orWhere('description', 'like', "%$request->search%")
                      ->orWhere('address', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        foreach ($request->get('order', []) as $key => $dir) {
            if ($dir == 'desc') {
                $query->orderByDesc($key ?? 'id');
            } else {
                $query->orderBy($key ?? 'id');
            }
        }
        // $query->orderByDesc('priority');

        $items = ($request->limit && ($request->limit <= 0 || $request->limit === 'all'))
            ? $query->get()
            : $query->paginate($request->limit)->withQueryString();

        return (new CategoryCollection($items))->additional([
            'message' => $items->isEmpty() ? __('There are no :0 for now.', ['categories']) : HttpStatus::message(HttpStatus::OK),
            'status' => $items->isEmpty() ? 'info' : 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return (new CategoryResource($category))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $this->validate($request, [
            'image' => ['sometimes', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'title' => ['required', 'string', Rule::unique('categories')->ignore($category->id)],
            'description' => ['required', 'string', 'min:15'],
            'priority' => ['required', 'numeric'],
            'type' => ['required', 'string', 'in:market,warehouse'],
        ]);

        $category->title = $request->title;
        $category->description = $request->description;
        $category->priority = $request->priority;
        $category->type = $request->type;
        $category->save();

        return (new CategoryResource($category))->additional([
            'message' => "$category->title has been updated.",
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = Category::whereId($item)->first();
                if ($item) {
                    if ($new_category = Category::whereNotIn('id', $request->items)->inRandomOrder()->first()) {
                        $item->services()->update([
                            'category_id' => $new_category->id,
                        ]);
                        $item->inventories()->update([
                            'category_id' => $new_category->id,
                        ]);
                    }

                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->title : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $count->count() === 1 ? "{$count->first()} has been deleted" : "{$count->count()} items have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Category::findOrFail($id);
            if ($new_category = Category::where('id', '!=', $item->id)->inRandomOrder()->first()) {
                $item->services()->update([
                    'category_id' => $new_category->id,
                ]);
                $item->inventories()->update([
                    'category_id' => $new_category->id,
                ]);
            }

            $item->delete();

            return $this->buildResponse([
                'message' => "\"{$item->title}\" has been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}