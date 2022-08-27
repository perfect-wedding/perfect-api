<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\OfferingCollection;
use App\Http\Resources\v1\Home\OfferingResource;
use App\Models\v1\Home\HomepageOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomepageOfferingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = HomepageOffering::query();

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

        return (new OfferingCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
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
            'subtitle' => ['nullable', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'image2' => ['nullable', 'mimes:jpg,png'],
            'icon' => ['nullable', 'string'],
            'features' => ['required', 'array'],
            'template' => ['nullable', 'string', 'in:OfferingsContainer'],
        ]);

        $content = new HomepageOffering([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'icon' => $request->icon,
            'features' => $request->features ?? [],
            'template' => $request->template ?? 'OfferingsContainer',
        ]);
        $content->save();

        return (new OfferingResource($content))->additional([
            'message' => 'New offering created successfully',
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
    public function show(HomepageOffering $offering)
    {
        Gate::authorize('can-do', ['website']);

        return (new OfferingResource($offering))->additional([
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
    public function update(Request $request, HomepageOffering $offering)
    {
        Gate::authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'subtitle' => ['nullable', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'image2' => ['nullable', 'mimes:jpg,png'],
            'icon' => ['nullable', 'string'],
            'features' => ['required', 'array'],
            'template' => ['nullable', 'string', 'in:OfferingsContainer'],
        ]);

        $offering->title = $request->title;
        $offering->subtitle = $request->subtitle;
        $offering->features = $request->features ?? [];
        $offering->icon = $request->icon;
        $offering->template = $request->template ?? 'OfferingsContainer';
        $offering->save();

        return (new OfferingResource($offering))->additional([
            'message' => "\"{$offering->title}\" has been updated successfully",
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
                $offering = HomepageOffering::find($id);
                if ($offering) {
                    return $offering->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} offerings have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $offering = HomepageOffering::findOrFail($id);
        }

        if ($offering) {
            $offering->delete();

            return $this->buildResponse([
                'message' => "{$offering->title} has been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested offering no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}
