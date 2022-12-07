<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\ServiceCollection;
use App\Http\Resources\v1\Business\ServiceResource;
use App\Http\Resources\v1\ReviewCollection;
use App\Models\v1\Company;
use App\Models\v1\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @param  string|null  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Company $company, $type = null)
    {
        $limit = $request->input('limit', 15);
        $type = $request->input('type', $type);
        $query = $company->services();

        if ($type && in_array($type, ['top', 'most-ordered', 'most-reviewed'])) {
            $services = $query->orderingBy($type)->paginate($limit);
        } else {
            $services = $query->paginate($limit);
        }

        return (new ServiceCollection($services))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stores(Request $request, Company $company)
    {
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3', 'max:50'],
            'category_id' => ['required', 'numeric'],
            'price' => ['required', 'numeric', 'min:1'],
            'stock' => ['nullable', 'numeric', 'min:1'],
            'basic_info' => ['required', 'string', 'min:3', 'max:55'],
            'short_desc' => ['required', 'string', 'min:3', 'max:75'],
            'details' => ['required', 'string', 'min:3', 'max:550'],
            'image' => ['sometimes', 'image', 'mimes:jpg,png'],
        ]);

        $service = new Service;
        $service->user_id = Auth::id();
        $service->company_id = $company->id;
        $service->category_id = $request->category_id;
        $service->title = $request->title;
        $service->type = 'market';
        $service->price = $request->price;
        $service->stock = $request->stock;
        $service->basic_info = $request->basic_info;
        $service->short_desc = $request->short_desc;
        $service->details = $request->details;
        $service->save();

        return (new ServiceResource($service))->additional([
            'message' => "{$service->title} has been created successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company, $id)
    {
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3', 'max:50'],
            'company_id' => ['required', 'numeric'],
            'category_id' => ['required', 'numeric'],
            'price' => ['required', 'numeric', 'min:1'],
            'stock' => ['nullable', 'numeric', 'min:1'],
            'basic_info' => ['required', 'string', 'min:3', 'max:55'],
            'short_desc' => ['required', 'string', 'min:3', 'max:75'],
            'details' => ['required', 'string', 'min:3', 'max:550'],
            'image' => ['sometimes', 'image', 'mimes:jpg,png'],
        ]);

        $service = $company->services()->findOrFail($id);
        $service->user_id = Auth::id();
        $service->company_id = $request->company_id;
        $service->category_id = $request->category_id;
        $service->title = $request->title;
        $service->type = 'market';
        $service->price = $request->price;
        $service->stock = $request->stock;
        $service->basic_info = $request->basic_info;
        $service->short_desc = $request->short_desc;
        $service->details = $request->details;
        $service->save();

        return (new ServiceResource($service))->additional([
            'message' => "{$service->title} has been updated successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Display the services.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reviews(Service $service)
    {
        return (new ReviewCollection($service->reviews()->with('user')->paginate()))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $company, $service)
    {
        $service = Service::findOrFail($service);

        return (new ServiceResource($service))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = Service::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} items have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Service::findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "\"{$item->title}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}