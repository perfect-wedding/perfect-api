<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\Admin\HomepageCollection;
use App\Http\Resources\v1\Home\Admin\HomepageResource;
use App\Models\v1\Home\Homepage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomepageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Homepage::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->whereFullText('meta', $request->search);
                $query->orWhere('title', $request->search);
                $query->orWhereHas('content', function ($q) use ($request) {
                    $q->whereFullText('content', $request->search);
                });
            });
        }

        // Reorder Columns
        if ($request->order === 'latest') {
            $query->latest();
        } elseif ($request->order === 'oldest') {
            $query->oldest();
        } elseif ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        return (new HomepageCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'meta' => ['nullable', 'string', 'min:10'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:10240'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif', 'max:1020'],
            'title' => ['required', 'string', 'min:3'],
            'details' => ['nullable', 'string', 'min:10'],
            'template' => ['nullable', 'string', 'in:Landing/AboutLayout,Landing/BoxWeddingLayout,Landing/MarryNowLayout,Landing/StressFreeLayout'],
            'default' => ['nullable', 'boolean'],
            'scrollable' => ['nullable', 'boolean'],
            'landing' => ['nullable', 'boolean'],
        ]);

        $content = new Homepage;
        $content->meta = $request->meta;
        $content->title = $request->title;
        $content->details = $request->details;
        $content->template = $request->template;
        $content->scrollable = $request->scrollable ?? false;
        $content->landing = $request->landing ?? false;
        if ($request->default) {
            if (($default = Homepage::whereDefault(true))->exists()) {
                $default->default = false;
                $default->save();
            }
            $content->default = $request->default;
        }
        $content->save();

        return (new HomepageResource($content))->additional([
            'message' => 'New page created successfully',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Homepage $homepage)
    {
        $this->authorize('can-do', ['website']);

        return (new HomepageResource($homepage))->additional([
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
    public function update(Request $request, Homepage $homepage)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'meta' => ['nullable', 'string', 'min:10'],
            'title' => ['required', 'string', 'min:3'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:10240'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif', 'max:1020'],
            'details' => ['nullable', 'string', 'min:10'],
            'template' => ['nullable', 'string', 'in:Landing/AboutLayout,Landing/BoxWeddingLayout,Landing/MarryNowLayout,Landing/StressFreeLayout'],
            'default' => ['nullable', 'boolean'],
            'scrollable' => ['nullable', 'boolean'],
            'landing' => ['nullable', 'boolean'],
        ]);

        $homepage->meta = $request->meta;
        $homepage->title = $request->title;
        $homepage->details = $request->details;
        $homepage->template = $request->template;
        $homepage->scrollable = $request->scrollable;
        $homepage->landing = $request->landing ?? false;
        if ($request->default) {
            if ($default = Homepage::whereDefault(true)->whereNot('id', $homepage->id)->first()) {
                $default->default = false;
                $default->save();
            }
            $homepage->default = $request->default;
        }
        $homepage->save();

        return (new HomepageResource($homepage))->additional([
            'message' => "\"{$homepage->title}\" has been updated successfully",
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    public function reorder(Request $request, $id)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'data' => ['required', 'array'],
            'data.*.id' => ['required', 'integer'],
            'data.*.priority' => ['required', 'integer'],
        ], [
            'data.*.id.required' => 'The id field is required for each navigation item',
            'data.*.priority.required' => 'The priority field is required for each navigation item',
        ]);

        $homepage = null;

        // Loop through the data and update the priority
        foreach ($request->data as $item) {
            $homepage = Homepage::find($item['id']);
            $homepage && $homepage->update([
                'priority' => $item['priority'],
            ]);
        }

        return (new HomepageResource($homepage))->additional([
            'message' => __("Navigation :0 has been updated successfully", [
                str('priority')->plural(count($request->data)),
            ]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['website']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($id) {
                $item = Homepage::whereId($id)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} pages have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $item = Homepage::whereId($id)->first();
        }

        if ($item) {
            $item->delete();

            return $this->buildResponse([
                'message' => "{$item->title} has been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested page no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}
