<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PlanResource;
use App\Models\v1\Company;
use App\Models\v1\Plan;
use App\Services\Media;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function settings(Request $request)
    {
        $f_companies = Company::where('featured_to', '>=', Carbon::now())->inRandomOrder()->limit(3)->get();

        return (new Media)->buildResponse([
            'message' => 'OK',
            'status' =>  'success',
            'status_code' => 200,
            'settings' => collect(config('settings'))->except(['permissions', 'messages', 'stripe_secret_key', 'ipinfo_access_token']),
            'featured_companies' => $f_companies->map(fn ($c) =>collect($c)->except(['user_id', 'status', 'phone'])),
            'plans' => PlanResource::collection(Plan::where(['status' => true])->get()),
            'csrf_token' => csrf_token(),
        ]);
    }
}
