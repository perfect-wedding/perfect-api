<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\ContentCollection;
use App\Http\Resources\v1\Home\ContentResource;
use App\Models\v1\Home\Homepage;
use App\Models\v1\Home\HomepageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HomepageContentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Homepage $homepage)
    {
        $query = $homepage->content();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->whereFullText('content', $request->search);
                $query->orWhere('title', $request->search);
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
        return (new ContentCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
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
            'subtitle' => ['nullable', 'string', 'min:3'],
            'content' => ['nullable', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'image2' => ['nullable', 'mimes:jpg,png'],
            'parent' => [Rule::requiredIf(fn () => !!$request->linked), 'string', 'exists:homepage_contents,slug'],
            'linked' => ['nullable', 'boolean'],
            'iterable' => ['nullable', 'boolean'],
            'attached' => [
                Rule::requiredIf(fn () => !!$request->iterable && !$request->linked), 'array',
                'in:HomepageService,HomepageTeam,HomepageOffering,HomepageTestimonial'
            ],
            'template' => ['nullable', 'string', 'in:HomeContainer'],
        ]);

        $content = new HomepageContent([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'content' => $request->content,
            'parent' => $request->parent,
            'linked' => $request->linked ?? false,
            'iterable' => $request->iterable ?? false,
            'attached' => $request->attached,
            'template' => $request->template ?? 'HomeContainer'
        ]);
        $homepage->content()->save($content);

        return (new ContentResource($content))->additional([
            'message' => "New page content created successfully",
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

        $content = $homepage->content()->findOrFail($id);

        return (new ContentResource($content))->additional([
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
    public function update(Request $request, Homepage $homepage, $id)
    {
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'subtitle' => ['nullable', 'string', 'min:3'],
            'content' => ['nullable', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'image2' => ['nullable', 'mimes:jpg,png'],
            'parent' => [Rule::requiredIf(fn () => !$request->linked), 'string', 'exists:homepage_contents,slug'],
            'linked' => ['nullable', 'boolean'],
            'iterable' => ['nullable', 'boolean'],
            'attached' => [
                Rule::requiredIf(fn () => !!$request->iterable && !$request->linked), 'array',
                'in:HomepageService,HomepageTeam,HomepageOffering,HomepageTestimonial'
            ],
            'template' => ['nullable', 'string', 'in:HomeContainer'],
        ]);

        $content = $homepage->content()->findOrFail($id);

        $content->title = $request->title;
        $content->subtitle = $request->subtitle;
        $content->content = $request->content;
        $content->image = $request->image;
        $content->parent = $request->parent;
        $content->linked = $request->linked ?? false;
        $content->iterable = $request->iterable ?? false;
        $content->attached = $request->attached;
        $content->template = $request->template ?? 'HomeContainer';
        $content->save();

        return (new ContentResource($content))->additional([
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
                $content = $homepage->content()->find($id);
                if ($content) {
                    return $content->delete();
                }

                return false;
            })->filter(fn ($i) =>$i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} contents have been deleted.",
                'status' =>  'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $content = $homepage->content()->findOrFail($id);
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
            'message' => 'The requested content no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}
