<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\NewsletterCollection;
use App\Http\Resources\v1\NewsletterResource;
use Illuminate\Http\Request;
use App\Models\v1\NewsLetter;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', ['anything']);
        $limit = $request->get('limit', 30);

        $query = NewsLetter::query();

        if ($request->has('search')) {
            $query->where('subject', 'like', "%{$request->get('search')}%")
                ->orWhereFullText('message', $request->get('search'))
                ->orWhere('type', $request->get('search'))
                ->orWhereJsonContains('recipients', $request->get('search'))
                ->orWhereHas('sender', function ($q) use ($request) {
                    $q->where('concat(firstname, " ", lastname)', 'like', "%{$request->get('search')}%");
                    $q->orWhere('email', 'like', "%{$request->get('search')}%");
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

        $list = $query->paginate($limit);

        return (new NewsletterCollection($list))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
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
    public function show(NewsLetter $newsletter)
    {
        return (new NewsletterResource($newsletter))->additional([
            'success' => true,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['anything']);
        if ($request->items) {
            $items = collect($request->items)->map(function ($item) use ($request) {
                $item = NewsLetter::whereId($item)->first();
                if ($item) {
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->id : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $items->count() === 1
                    ? __('News Letter list entry #:0 has been deleted', [$items->first()])
                    : __(':0 News Letter list entry have been deleted.', [$items->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = NewsLetter::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __('News Letter list entry #:0 has been deleted.', [$item->id]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
