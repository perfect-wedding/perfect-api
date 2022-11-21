<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\ServiceCollection;
use App\Http\Resources\v1\Home\ServiceResource;
use App\Models\v1\Home\HomepageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomepageServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = HomepageService::query();

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

        return (new ServiceCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
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
            'title' => ['required', 'string', 'min:3'],
            'content' => ['nullable', 'string', 'min:10'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'image2' => ['nullable', 'mimes:jpg,png'],
            'icon' => ['nullable', 'string'],
            'parent' => ['nullable', 'exists:homepages,id'],
            'type' => ['nullable', 'string', 'in:feature,client,default'],
            'template' => ['nullable', 'string', 'in:ServicesContainer'],
        ]);

        $service = new HomepageService([
            'title' => $request->title,
            'content' => $request->content,
            'icon' => $request->icon,
            'parent' => $request->parent,
            'type' => $request->type == 'default' ? null : $request->type,
            'template' => $request->template ?? 'ServicesContainer',
        ]);
        $service->save();

        return (new ServiceResource($service))->additional([
            'message' => 'New service created successfully',
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
    public function show(HomepageService $service)
    {
        $this->authorize('can-do', ['website']);

        return (new ServiceResource($service))->additional([
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
    public function update(Request $request, HomepageService $service)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3'],
            'content' => ['nullable', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'image2' => ['nullable', 'mimes:jpg,png'],
            'icon' => ['nullable', 'string'],
            'parent' => ['nullable', 'exists:homepages,id'],
            'type' => ['nullable', 'string', 'in:feature,client,default'],
            'template' => ['nullable', 'string', 'in:ServicesContainer'],
        ]);

        $service->title = $request->title;
        $service->content = $request->content;
        $service->icon = $request->icon;
        $service->type = $request->type == 'default' ? null : $request->type;
        $service->parent = $request->parent;
        $service->template = $request->template ?? 'ServicesContainer';
        $service->save();

        return (new ServiceResource($service))->additional([
            'message' => "\"{$service->title}\" has been updated successfully",
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
                $service = HomepageService::find($id);
                if ($service) {
                    return $service->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} services have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $service = HomepageService::findOrFail($id);
        }

        if ($service) {
            $service->delete();

            return $this->buildResponse([
                'message' => "{$service->title} has been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested service no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}
