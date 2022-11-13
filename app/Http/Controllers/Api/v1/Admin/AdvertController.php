<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\AdvertCollection;
use App\Http\Resources\v1\AdvertResource;
use App\Models\v1\Advert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdvertController extends Controller
{
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        return Validator::make($request->all(), array_merge([
            'media' => ['sometimes', 'file', 'mimes:png,jpg,jpeg,mp4,gif', 'max:26214400'],
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'details' => ['required', 'string'],
            'icon' => ['nullable', 'string'],
            'places' => ['sometimes', 'array'],
            'meta' => ['sometimes', 'array'],
            'active' => ['required', 'boolean'],
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
        $this->authorize('can-do', ['advert.manage']);
        $query = Advert::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->whereFulltext('details', $request->search)
                ->orWhere('title', 'like', "%$request->search%");
            });
        }

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

        if ($request->has('meta') && is_array($request->meta)) {
            $query->where('meta->' . $request->meta['key'], $request->meta['value']);
        }

        if ($request->has('places')) {
            $places = [];
            $places = $request->places
                ? array_merge($places, is_array($request->places) ? $request->places : [$request->places])
                : ['all'];

            $query->place($places);
        }

        if ($request->paginate === 'none') {
            $ads = $query->get();
        } elseif ($request->paginate === 'cursor') {
            $ads = $query->cursorPaginate($request->get('limit', 15))->withQueryString();
        } else {
            $ads = $query->paginate($request->get('limit', 15))->withQueryString();
        }

        return (new AdvertCollection($ads))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function show(Request $request, Advert $advertisement)
    {
        $this->authorize('can-do', ['advert.manage']);
        return (new AdvertResource($advertisement))->additional([
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
    public function store(Request $request)
    {
        $this->authorize('can-do', ['advert.manage']);
        $this->validate($request, []);

        $advert = new Advert;

        $advert->title = $request->title;
        $advert->details = $request->details;
        $advert->icon = $request->icon;
        $advert->active = $request->active;
        $advert->meta = $request->meta;
        $advert->places = $request->places ?? ['all'];

        $advert->save();

        return (new AdvertResource($advert))->additional([
            'message' => __(':0 has been saved.', [$advert->title]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Advert $advertisement)
    {
        $this->authorize('can-do', ['advert.manage']);
        $this->validate($request, []);

        $advert = $advertisement;
        $advert->title = $request->title;
        $advert->details = $request->details;
        $advert->icon = $request->icon;
        $advert->active = $request->active;
        $advert->meta = $request->meta;
        $advert->places = $request->places ?? ['all'];

        $advert->save();

        return (new AdvertResource($advert))->additional([
            'message' => __(':0 has been updated.', [$advert->title]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['advert.manage']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = Advert::whereId($item)->first();
                if ($item) {
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->title : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $count->count() === 1
                    ? __(':0 has been deleted', [$count->first()])
                    : __(':0 items have been deleted.', [$count->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Advert::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}