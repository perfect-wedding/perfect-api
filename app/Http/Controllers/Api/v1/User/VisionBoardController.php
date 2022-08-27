<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\AlbumCollection;
use App\Http\Resources\v1\User\AlbumResource;
use App\Models\v1\VisionBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VisionBoardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $boards = $user->boards()->paginate();

        return (new AlbumCollection($boards))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:100'],
            'info' => ['required', 'string', 'max:150'],
            'privacy' => ['nullable', 'string', 'max:500'],
            'disclaimer' => ['nullable', 'string', 'max:500'],
        ], [
        ])->validate();

        $user = Auth::user();
        $album = [
            'title' => $request->title,
            'info' => $request->info,
            'privacy' => $request->privacy,
            'disclaimer' => $request->disclaimer,
        ];

        $album = $user->boards()->save(new VisionBoard($album));

        return (new AlbumResource($album))->additional([
            'message' => 'You have succesfully created a new vision board.',
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
    public function show($id)
    {
        $user = Auth::user();
        $album = $user->boards()->whereId($id)->orWhere('slug', $id)->firstOrFail();

        return (new AlbumResource($album))->additional([
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
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:100'],
            'info' => ['required', 'string', 'max:150'],
            'privacy' => ['nullable', 'string', 'max:500'],
            'disclaimer' => ['nullable', 'string', 'max:500'],
        ], [
        ])->validate();

        $user = Auth::user();
        $album = $user->boards()->findOrFail($id);
        $album->title = $request->title;
        $album->info = $request->info;
        $album->privacy = $request->privacy;
        $album->disclaimer = $request->disclaimer;

        $album->save();

        return (new AlbumResource($album))->additional([
            'message' => 'Your album was updated succesfully.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = VisionBoard::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} boards have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = VisionBoard::findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "Album \"{$item->title}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
