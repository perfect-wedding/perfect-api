<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\SlidesCollection;
use App\Http\Resources\v1\Home\SlidesResource;
use App\Models\v1\Home\Homepage;
use App\Models\v1\Home\HomepageContent;
use App\Models\v1\Home\HomepageSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HomepageSlidesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Homepage $homepage)
    {
        $query = $homepage->slides();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', $request->search);
                $query->orWhere('subtitle', $request->search);
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }
        return (new SlidesCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Homepage $homepage)
    {
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'subtitle' => ['required', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'color' => ['nullable', 'string', Rule::in($colors = [
                'rgb(196,0,12)',
                'rgb(252,212,179)',
                'rgb(252,246,187)',
                'rgb(242,146,182)',
                'rgb(255,214,17)',
                'rgb(255,87,0)',
                'rgb(228,194,193)',
                'rgb(99,84,66)',
                'rgb(34,17,5)',
                'rgb(253,121,0)',
                'rgb(222,146,115)',
                'rgb(72,13,26)',
                'rgb(15,47,23)'
            ])],
        ], ['color.in' => 'Color should be one of '. implode(', ', $colors)]);

        $content = new HomepageSlide([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'color' => $request->color,
        ]);
        $homepage->slides()->save($content);

        return (new SlidesResource($content))->additional([
            'message' => "New slide created successfully",
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Homepage $homepage, $id)
    {
        Gate::authorize('can-do', ['website']);

        $content = $homepage->slides()->findOrFail($id);

        return (new SlidesResource($content))->additional([
            'message' => "New slide created successfully",
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
    public function update(Request $request, Homepage $homepage, $id)
    {
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'subtitle' => ['required', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'color' => ['nullable', 'string', Rule::in($colors = [
                'rgb(196,0,12)',
                'rgb(252,212,179)',
                'rgb(252,246,187)',
                'rgb(242,146,182)',
                'rgb(255,214,17)',
                'rgb(255,87,0)',
                'rgb(228,194,193)',
                'rgb(99,84,66)',
                'rgb(34,17,5)',
                'rgb(253,121,0)',
                'rgb(222,146,115)',
                'rgb(72,13,26)',
                'rgb(15,47,23)'
            ])],
        ], ['color.in' => 'Color should be one of '. implode(', ', $colors)]);

        $content = $homepage->slides()->findOrFail($id);

        $content->title = $request->title;
        $content->subtitle = $request->subtitle;
        $content->color = $request->color;
        $content->save();

        return (new SlidesResource($content))->additional([
            'message' => "\"{$content->title}\" has been updated successfully",
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Homepage $homepage, $id = null)
    {
        Gate::authorize('can-do', ['website']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($id) use ($homepage) {
                $content = $homepage->slides()->find($id);
                if ($content) {
                    return $content->delete();
                }

                return false;
            })->filter(fn ($i) =>$i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} slides have been deleted.",
                'status' =>  'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $content = $homepage->slides()->findOrFail($id);
        }

        if ($content) {
            $content->delete();

            return $this->buildResponse([
                'message' => "{$content->title} has been deleted.",
                'status' =>  'success',
                'status_code' => HttpStatus::OK,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested slide no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}