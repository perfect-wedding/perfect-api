<?php

namespace App\Http\Controllers\Api\v1\Tools;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\v1\VisionBoard;
use App\Traits\Extendable;
use ColorThief\ColorThief;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ColorExtractor extends Controller
{
    use Extendable;

    protected $pathMap = [
        'board' => 'files/images/',
    ];

    public function index(Request $request, $filename = null)
    {
        $src = $request->filename ?? $filename;

        if ($src && is_array($src)) {
            $palette = collect($src)->map(function ($src, $key) {
                if ($src) {
                    return $this->loadPalette($src, 2);
                }

                return null;
            })->all();
        } elseif ($src) {
            $palette = $this->loadPalette($filename);
        }

        if ($palette && $request->board_id) {
            $board = VisionBoard::find($request->board_id);
            if ($board) {
                $board->meta = ['palette' => $palette ?? []];
                $board->save();
            }
        }

        return $this->buildResponse([
            'message' => HttpStatus::message(isset($palette) ? HttpStatus::OK : HttpStatus::UNPROCESSABLE_ENTITY),
            'status' => isset($palette) ? 'success' : 'error',
            'status_code' => isset($palette) ? HttpStatus::OK : HttpStatus::UNPROCESSABLE_ENTITY,
            'data' => [
                'palette' => $palette ?? [],
            ],
        ]);
    }

    /**
     * Uploads an image to the temporary directory, gets the color palette off
     * it and deletes the image afterwards
     *
     * @param  string  $filename
     * @return array
     */
    protected function loadPalette(string $filename, $colorCount = 6): array
    {
        $colors = [];
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            $temp = 'temp/'.time().'_'.rand().'.png';
            Storage::disk('local')->put($temp, file_get_contents($filename));
            if (Storage::exists($temp)) {
                $colors = ColorThief::getPalette(storage_path('app/'.$temp), $colorCount, 10, null, 'rgb');
                Storage::delete($temp);
            }
        } else {
            $path = $this->pathMap[request()->type] ?? '';
            if (Storage::exists($path.$filename)) {
                $colors = ColorThief::getPalette(storage_path('app/'.$path.$filename), $colorCount, 10, null, 'rgb');
            }
        }

        return $colors;
    }

    public function loadImage(Response $response, $filename = null)
    {
        $temp = 'temp/'.time().'.png';
        if ($filename) {
            $temp = 'temp/'.$filename;
        } else {
            Storage::disk('local')->put($temp, file_get_contents('http://localhost:8080/images/logo.png'));
        }

        $file = Storage::disk('local')->get($temp);

        return (new Response($file, HttpStatus::OK))
              ->header('Content-Type', 'image/'.collect(explode('.', $temp))->last());
    }
}