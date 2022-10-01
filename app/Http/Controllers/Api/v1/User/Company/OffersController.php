<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\OfferCollection;
use App\Http\Resources\v1\Business\OfferResource;
use App\Models\v1\Company;
use App\Models\v1\Offer;
use App\Models\v1\Service;
use Illuminate\Http\Request;

class OffersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company, Service $service)
    {
        $offers = $service->offers;

        return (new OfferCollection($offers))->additional([
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
    public function show(Request $request, $company, Service $service, $id)
    {
        return (new OfferResource($service->offers()->findOrFail($id)))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Company $company, $service)
    {
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3', 'max:25'],
            'amount' => ['required', 'numeric', 'min:1'],
            'featured' => ['required', 'boolean'],
            'type' => ['nullable', 'string', 'in:discount,increase'],
            'operator' => ['required', 'string', 'in:+,-,*,%'],
            'description' => ['required', 'string', 'min:3', 'max:250'],
        ]);

        $_service = $company->services()->findOrFail($service);
        $offer = new Offer();
        $offer->offerable_id = $_service->id;
        $offer->offerable_type = get_class($_service);
        $offer->title = $request->title;
        $offer->type = $request->type;
        $offer->amount = $request->amount;
        $offer->operator = $request->operator;
        $offer->featured = $request->featured ?? 0;
        $offer->description = $request->description ?? '';
        $offer->save();

        return (new OfferResource($offer))->additional([
            'message' => "{$offer->title} has been created successfully for {$_service->title}.",
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
    public function update(Request $request, Company $company, $service, $id)
    {
        $this->validate($request, [
            'title' => ['required', 'string', 'min:3', 'max:25'],
            'amount' => ['required', 'numeric', 'min:1'],
            'featured' => ['nullable', 'numeric', 'min:1'],
            'type' => ['nullable', 'string', 'in:discount,increase'],
            'operator' => ['required', 'string', 'in:+,-,*,%'],
            'description' => ['required', 'string', 'min:3', 'max:250'],
        ]);

        $offer = $company->services()->findOrFail($service)->offers()->findOrFail($id);
        $offer->title = $request->title;
        $offer->type = $request->type;
        $offer->amount = $request->amount;
        $offer->operator = $request->operator;
        $offer->featured = $request->featured;
        $offer->description = $request->description ?? '';
        $offer->save();

        return (new OfferResource($offer))->additional([
            'message' => "{$offer->title} has been updated successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company, Service $service, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($service) {
                $item = $service->offers()->whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} offers have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = $service->offers()->findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "\"{$item->title}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
