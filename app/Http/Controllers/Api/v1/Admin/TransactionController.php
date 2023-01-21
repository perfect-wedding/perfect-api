<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\TransactionCollection;
use App\Http\Resources\v1\User\TransactionResource;
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
        $this->authorize('can-do', ['transactions']);
        $limit = $request->get('limit', 30);
        $query = Transaction::orderByDesc('id');

        if ($status) {
            $query->where('status', $status);
        }

        if ($request->has('group')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->group);
                $q->orWhereHas('company', function ($q) use ($request) {
                    $q->where('type', $request->group);
                });
            });
        }
        $transaction = $query->paginate($limit);

        return (new TransactionCollection($transaction))->additional([
            'message' => 'OK',
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
    public function show($id)
    {
        $this->authorize('can-do', ['transactions']);

        $transaction = Transaction::whereReference($id)->orWhere('id', $id)->firstOrFail();

        return (new TransactionResource($transaction))->additional([
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
        $this->authorize('can-do', ['transactions']);

        $invoice = Transaction::whereReference($reference)->firstOrFail();

        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
            ...new TransactionCollection($invoice),
        ]);
    }
}