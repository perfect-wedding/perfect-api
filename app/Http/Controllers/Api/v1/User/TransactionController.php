<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\TransactionCollection;
use App\Models\v1\Transaction as Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $status = null)
    {
        $limit = $request->limit ?? 15;
        $query = Auth()->user()->transactions()->orderByDesc('id');

        if ($status) {
            $query->where('status', $status);
        }

        $transaction = $query->paginate($limit);

        return (new TransactionCollection($transaction))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function invoice(Request $request, $reference)
    {
        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
            ...new TransactionCollection(Transaction::whereReference($reference)->get()),
        ]);
    }
}
