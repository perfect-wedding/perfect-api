<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\WalletCollection;
use App\Http\Resources\v1\User\WalletResource;
use App\Models\v1\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function withdrawals(Request $request)
    {
        \Gate::authorize('can-do', ['users.manage']);

        $query = Wallet::whereType('withdrawal')->with('user');

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                if (is_numeric($request->search)) {
                    $query->where('amount', '>=', $request->search);
                } else {
                    $query->where('reference', 'like', "%$request->search%")
                        ->orWhere('detail', 'like', "%$request->search%")
                        ->orWhereHas('user', function ($query) use ($request) {
                            $query->where('email', $request->search);
                            $query->orWhere(DB::raw(
                                "REPLACE(CONCAT(COALESCE(firstname,''),' ',COALESCE(lastname,'')),'  ',' ')"
                            ), 'like', "%$request->search%");
                        });
                }
            });
        }

        // Reorder Columns
        if ($request->order && $request->order === 'latest') {
            $query->latest();
        } elseif ($request->order && $request->order === 'oldest') {
            $query->oldest();
        } elseif ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        if ($request->has('status')) {
            $query->statusIs($request->status, true);
        }

        $withdrawals = $query->paginate(15)->onEachSide(1)->withQueryString();

        return (new WalletCollection($withdrawals))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function setStatus(Request $request, Wallet $wallet)
    {
        \Gate::authorize('can-do', ['users.manage']);

        $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,complete,failed'],
        ]);

        $wallet->status = $request->status;
        $wallet->save();

        return (new WalletResource($wallet))->additional([
            'message' => __('The withdrawal status has been updated to :status.', ['status' => $request->status]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
