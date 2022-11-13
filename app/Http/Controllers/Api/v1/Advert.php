<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\AdvertCollection;
use App\Models\v1\Advert as V1Advert;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Advert extends Controller
{
    public function place(Request $request, Builder $query)
    {
        $places = [];
        $places = $request->places
            ? array_merge($places, is_array($request->places) ? $request->places : [$request->places])
            : ['all'];

        $query->place($places);

        return $query;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = $this->place($request, V1Advert::query());

        $query->active();//->notExpired();

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
}
