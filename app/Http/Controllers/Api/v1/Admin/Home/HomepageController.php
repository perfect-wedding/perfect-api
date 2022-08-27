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
        if ($request->order && is_array($request->order)) {
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
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'meta' => ['nullable', 'string', 'min:10'],
            'default' => ['nullable', 'boolean'],
            'scrollable' => ['nullable', 'boolean'],
        ]);

        $content = new Homepage;
        $content->title = $request->title;
        $content->meta = $request->meta;
        $content->scrollable = $request->scrollable ?? false;
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
        Gate::authorize('can-do', ['website']);

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
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'meta' => ['nullable', 'string', 'min:10'],
            'default' => ['nullable', 'boolean'],
            'scrollable' => ['nullable', 'boolean'],
        ]);

        $homepage->title = $request->title;
        $homepage->meta = $request->meta;
        $homepage->scrollable = $request->scrollable;
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

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        Gate::authorize('can-do', ['website']);
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
