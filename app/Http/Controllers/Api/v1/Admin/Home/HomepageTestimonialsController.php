<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\TestimonialCollection;
use App\Http\Resources\v1\Home\TestimonialResource;
use App\Models\v1\Home\HomepageTestimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomepageTestimonialsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = HomepageTestimonial::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('title', $request->search);
                $query->orWhere('content', $request->search);
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
        return (new TestimonialCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
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
            'author' => ['required', 'string', 'min:3'],
            'title' => ['required', 'string', 'min:3'],
            'content' => ['nullable', 'string', 'min:10'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'template' => ['nullable', 'string', 'in:TestimonyContainer'],
        ]);

        $content = new HomepageTestimonial([
            'author' => $request->author,
            'title' => $request->title,
            'content' => $request->content,
            'template' => $request->template ?? 'TestimonyContainer'
        ]);
        $content->save();

        return (new TestimonialResource($content))->additional([
            'message' => "New testimonial created successfully",
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
    public function show(HomepageTestimonial $testimonial)
    {
        Gate::authorize('can-do', ['website']);

        return (new TestimonialResource($testimonial))->additional([
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
    public function update(Request $request, HomepageTestimonial $testimonial)
    {
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'author' => ['required', 'string', 'min:3'],
            'title' => ['required', 'string', 'min:3'],
            'content' => ['nullable', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'template' => ['nullable', 'string', 'in:TestimonyContainer'],
        ]);

        $testimonial->author = $request->author;
        $testimonial->title = $request->title;
        $testimonial->content = $request->content;
        $testimonial->template = $request->template ?? 'TestimonyContainer';
        $testimonial->save();

        return (new TestimonialResource($testimonial))->additional([
            'message' => "{$testimonial->author}'s testimonial has been updated successfully",
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
                $testimonial = HomepageTestimonial::find($id);
                if ($testimonial) {
                    return $testimonial->delete();
                }

                return false;
            })->filter(fn ($i) =>$i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} testimonials have been deleted.",
                'status' =>  'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $testimonial = HomepageTestimonial::findOrFail($id);
        }

        if ($testimonial) {
            $testimonial->delete();

            return $this->buildResponse([
                'message' => "{$testimonial->author}'s testimonial has been deleted.",
                'status' =>  'success',
                'status_code' => HttpStatus::OK,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested testimonial no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}