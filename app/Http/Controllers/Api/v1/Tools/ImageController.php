<?php

namespace App\Http\Controllers\Api\v1\Tools;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\v1\Image;
use App\Models\v1\VisionBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'file' => ['required', 'image', 'mimes:png,jpg'],
            'type' => ['required', 'string', 'in:Album,Vision'],
            'type_id' => ['required', 'numeric'],
        ], [
        ])->validate();

        $user = Auth::user();
        if ($request->type === 'Album') {
            $imageable = $user->albums()->findOrFail($request->type_id);
        } elseif ($request->type === 'Vision') {
            $imageable = $user->boards()->findOrFail($request->type_id);
        }

        $image = $imageable->images()->create([
            'model' => $request->type,
            'meta' => is_string($request->meta) ? json_decode($request->meta) : $request->meta,
        ]);

        return $this->buildResponse([
            'data' => collect($image)->merge(['file_id' => $image->id]),
            'message' => 'Upload Successfull',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
            'description' => ['required', 'string', 'max:500'],
        ], [
        ])->validate();

        $image = Image::findOrFail($id);
        $image->description = $request->description;
        $image->save();

        return $this->buildResponse([
            'data' => collect($image)->merge(['file_id' => $image->id]),
            'message' => 'Image description updated successfully.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $imageable_id
     * @return \Illuminate\Http\Response
     */
    public function updateGrid(Request $request, $type, $imageable_id)
    {
        Validator::make($request->all(), [
            'images' => ['required', 'array'],
        ], [
        ])->validate();

        if ($type === 'boards') {
            $imageable = VisionBoard::findOrFail($imageable_id);
            $title = 'Vision board';
        } else {
            $imageable = VisionBoard::findOrFail($imageable_id);
            $title = 'Something else';
        }
        if ($request->palette_grid) {
            $imageable->meta = collect($imageable->meta)->put('palette_grid', $request->palette_grid);
            $imageable->save();
        }
        collect($request->images)->each(function ($item) use ($imageable) {
            $image = $imageable->images()->findOrFail($item['id']);
            $image->meta = collect($image->meta)->put('grid', $item['meta']['grid'] ?? []);
            $image->meta = $item['meta'];
            $image->save();
        });

        return $this->buildResponse([
            'message' => "$title updated successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
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
                $item = Image::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} images have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Image::findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => 'Image has been deleted.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
