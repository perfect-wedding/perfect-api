<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BulletinCollection;
use App\Http\Resources\v1\BulletinResource;
use App\Models\v1\Bulletin;
use App\Models\v1\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BulletinController extends Controller
{
    public function audience(Request $request, User $user, Builder $query)
    {
        $audience = [];
        $audience = $request->audience
            ? array_merge($audience, is_array($request->audience) ? $request->audience : [$request->audience])
            : [];

        if ($user->role === 'concierge') {
            $audience[] = 'concierge';
        } elseif ($user->role !== 'admin') {
            if ($user->company) {
                $audience[] = $user->company->type;
            } else {
                $audience[] = 'user';
            }
        } else {
            $audience[] = 'all';
        }

        $query->audience($audience);

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
        $user = $request->user();

        $query = $this->audience($request, $user, Bulletin::query());

        $query->active()->notExpired();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->whereFulltext('content', $request->search)
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

    public function show(Request $request, Bulletin $bulletin)
    {
        $user = $request->user();

        if ($user->company) {
            $bulletin->audience(['all', $user->company->type]);
        } else {
            $bulletin->audience(['n/a']);
        }

        $bulletin->active()->notExpired();

        return (new BulletinResource($bulletin))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
