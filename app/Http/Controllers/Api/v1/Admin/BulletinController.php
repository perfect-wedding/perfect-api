<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BulletinCollection;
use App\Http\Resources\v1\BulletinResource;
use App\Models\v1\Bulletin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BulletinController extends Controller
{
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        return Validator::make($request->all(), array_merge([
            'thumbnail' => ['sometimes', 'file', 'mimes:png,jpg,jpeg', 'max:1024'],
            'media' => ['sometimes', 'file', 'mimes:png,jpg,jpeg,mp4,gif', 'max:26214400'],
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'subtitle' => ['required', 'string', 'min:3', 'max:100'],
            'content' => ['required', 'string', 'min:15'],
            'expiry' => ['sometimes', 'date'],
            'audience' => ['sometimes', 'array'],
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
        \Gate::authorize('can-do', ['configuration']);
        $query = Bulletin::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->whereFulltext('content',  $request->search)
                ->orWhere('title', 'like', "%$request->search%")
                ->orWhere('subtitle', 'like', "%$request->search%");
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

        // Reorder Columns
        if ($request->has('audience')) {
            $query->audience([$request->audience,$request->audience,$request->audience]);
        }

        if ($request->paginate === 'cursor') {
            $bulletins = $query->cursorPaginate($request->get('limit', 15))->withQueryString();
        } else {
            $bulletins = $query->paginate($request->get('limit', 15))->withQueryString();
        }

        return (new BulletinCollection($bulletins))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    function show(Request $request, Bulletin $bulletin)
    {
        return (new BulletinResource($bulletin))->additional([
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
        $this->validate($request, []);

        $bulletin = new Bulletin;

        $bulletin->title = $request->title;
        $bulletin->subtitle = $request->subtitle;
        $bulletin->content = $request->content;
        $bulletin->expires_at = $request->expiry ?? null;
        $bulletin->active = $request->active;
        $bulletin->audience = $request->audience;
        $bulletin->save();

        return (new BulletinResource($bulletin))->additional([
            'message' => __(":0 has been saved.", [$bulletin->title]),
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
    public function update(Request $request, Bulletin $bulletin)
    {
        $this->validate($request, []);

        $bulletin->title = $request->title;
        $bulletin->subtitle = $request->subtitle;
        $bulletin->content = $request->content;
        $bulletin->expires_at = $request->expiry ?? null;
        $bulletin->active = $request->active;
        $bulletin->audience = $request->audience ?? $bulletin->audience ?? ['all'];
        $bulletin->save();

        return (new BulletinResource($bulletin))->additional([
            'message' => __(":0 has been updated.", [$bulletin->title]),
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
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = Bulletin::whereId($item)->first();
                if ($item) {
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->title : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $count->count() === 1
                    ? __(":0 has been deleted", [$count->first()])
                    : __(":0 items have been deleted.", [$count->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Bulletin::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(":0 has been deleted.", [$item->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}