<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ConciergeController extends Controller
{
    public function index(Request $request)
    {
        $app_data = [
            'page' => 'concierge.dashboard',
            'title' => 'Concierge Dashboard',
            'vendor_page' => 'shop',
            'count_users' => User::count(),
            'users' => User::paginate(15),
        ];

        $app_data['appData'] = collect($app_data);

        return view('concierge.dashboard', $app_data);
    }

    public function user(Request $request, User $user)
    {
        $app_data = [
            'page' => 'concierge.dashboard',
            'title' => $user->name.' Profile',
            'vendor_page' => 'shop',
            'count_users' => User::count(),
            'users' => User::paginate(15),
            'user' => $user,
        ];

        $app_data['appData'] = collect($app_data);

        return view('concierge.user', $app_data);
    }

    public function verify(Request $request, $type, User $user)
    {
        if ($type === 'user') {
            $user->verified = true;
            $user->save();
        }

        return back()->with([
            'message' => [
                'msg' => ucwords($type).' succesfully verified',
                'type' => 'success',
            ],
        ]);
    }
}
