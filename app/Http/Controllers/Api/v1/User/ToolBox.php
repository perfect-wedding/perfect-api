<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserCollection;
use App\Models\v1\User;
use Illuminate\Http\Request;

class ToolBox extends Controller
{
    public function contacts(Request $request)
    {
        $user = $request->user();

        $query = User::whereHas('company', function ($q) use ($user) {
            $q->whereIn('id', function ($q2) use ($user) {
                $q2->select('company_id')->from('orders')->where('user_id', $user->id);
            });
        })->orWhereIn('company_id', function ($q) use ($user) {
            $q->select('company_id')->from('orders')->where('user_id', $user->id);
        });

        $customers = $query->paginate($request->get('limit', 30));

        return (new UserCollection($customers))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
