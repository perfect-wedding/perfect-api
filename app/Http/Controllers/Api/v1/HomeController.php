<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\HomepageCollection;
use App\Http\Resources\v1\Home\HomepageResource;
use App\Models\v1\Company;
use App\Models\v1\Home\Homepage;
use App\Models\v1\Home\HomepageContent;
use App\Services\Media;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $pages = Homepage::paginate();

        return (new HomepageCollection($pages))->response()->setStatusCode(HttpStatus::OK);
    }

    public function page($page = null)
    {
        if (isset($page)) {
            $page = Homepage::whereId($page)->orWhere('slug', $page)->first();
        } else {
            $page = Homepage::whereDefault(true)->first();
        }

        return (new HomepageResource($page))->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function settings(Request $request)
    {
        $f_companies = Company::where('featured_to', '>=', Carbon::now())->inRandomOrder()->limit(3)->get();
        $home_content = HomepageContent::where('linked', true)
                        ->where('slug', '!=', null)
                        ->where('slug', '!=', '')
                        ->get(['id', 'title', 'slug']);

        return (new Media)->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => 200,
            'settings' => collect(config('settings'))->except(['permissions', 'messages', 'stripe_secret_key', 'ipinfo_access_token']),
            'featured_companies' => $f_companies->map(fn ($c) => collect($c)->except(['user_id', 'status', 'phone'])),
            'website' => [
                'content' => $home_content,
                'attachable' => [
                    ['label' => 'Service', 'value' => 'HomepageService'],
                    ['label' => 'Team', 'value' => 'HomepageTeam'],
                    ['label' => 'Offering', 'value' => 'HomepageOffering'],
                    ['label' => 'Testimonial', 'value' => 'HomepageTestimonial'],
                ],
            ],
            'csrf_token' => csrf_token(),
        ]);
    }
}
