<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FeaturedCollection;
use App\Http\Resources\v1\FeaturedResource;
use App\Models\v1\Featured;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeaturedController extends Controller
{
    use Meta;

    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        return Validator::make($request->all(), array_merge([
            'type' => ['required', 'string', 'in:company,service,inventory'],
            'type_id' => ['required', 'numeric'],
            'plan_id' => ['nullable', 'numeric', 'exists:plans,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'tenure' => ['nullable', 'string', 'in:monthly,yearly,weekly,daily,hourly'],
            'meta' => ['nullable', 'array'],
            'places' => ['nullable', 'array'],
            'active' => ['nullable', 'in:true,false,0,1'],
            'recurring' => ['nullable', 'in:true,false,0,1'],
        ], $rules), $messages, $customAttributes)->validate();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Featured::query();

        // Reorder Columns
        if ($request->has('order') && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        if ($request->has('meta') && isset($request->meta['key']) && isset($request->meta['value'])) {
            $query->where('meta->'.$request->meta['key'], $request->meta['value']);
        }

        if ($request->has('places')) {
            $query->place(is_array($request->places) ? $request->places : [$request->places]);
        }

        if ($request->has('type') && in_array($request->type, ['company', 'service', 'inventory'])) {
            $query->whereFeatureableType(str($request->type)->ucfirst()->prepend('App\Models\v1\\')->__toString());
        }

        if ($request->has('status') && in_array($request->status, ['pending', 'approved'])) {
            $query->pending($request->status == 'pending');
        }

        if ($request->paginate === 'none') {
            $ads = $query->get();
        } elseif ($request->paginate === 'cursor') {
            $ads = $query->cursorPaginate($request->get('limit', 15))->withQueryString();
        } else {
            $ads = $query->paginate($request->get('limit', 15))->withQueryString();
        }

        return (new FeaturedCollection($ads))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function show(Request $request, Featured $featured)
    {
        $this->authorize('can-do', ['advert.manage']);

        return (new FeaturedResource($featured))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
