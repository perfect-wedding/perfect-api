<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\HomepageCollection;
use App\Http\Resources\v1\Home\HomepageResource;
use App\Models\v1\Company;
use App\Models\v1\Home\Homepage;
use App\Models\v1\Home\HomepageContent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ToneflixCode\LaravelFileable\Media;

class HomeController extends Controller
{
    protected $file_types = [
        'image' => '.jpg, .png, .jpeg',
        'video' => '.mp4',
        'all' => 'audio/*, video/*, image/*',
    ];

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

        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
            'settings' => collect(config('settings'))
                ->except(['permissions', 'messages', 'system'])
                ->filter(fn($v, $k)=>stripos($k, 'secret') === false)
                ->mergeRecursive([
                    'oauth' => [
                        'google' => collect(config('services.google'))->filter(fn($v, $k)=>stripos($k, 'secret') === false),
                        'facebook' => collect(config('services.facebook'))->filter(fn($v, $k)=>stripos($k, 'secret') === false),
                    ],
                ]),
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

    public function verificationData(Request $request, $action, $task = null)
    {
        $disk = Storage::disk('protected');
        if (! $disk->exists('company_verification_data.json')) {
            $disk->put('company_verification_data.json', "[]");
        }

        $data = collect(json_decode($disk->get('company_verification_data.json'), JSON_FORCE_OBJECT))->map(function($data) {
            if ($data['type'] === 'file') {
                $data['preview'] = $data['name'];
            } elseif ($data['type'] === 'checkbox') {
                $data['boolean'] = true;
                $data['highlight'] = true;
                $data['traditional'] = true;
            }
            return $data;
        });

        if ($action === 'save') {
            $this->validate($request, [
                'data' => ['required', 'array'],
                'data.*.label' => ['required', 'string'],
                'data.*.type' => ['required', 'string', 'in:text,checkbox,number,file'],
                'data.*.col' => ['required', 'numeric', 'min:1', 'max:12'],
                'data.*.file_type' => ['required_if:data.*.field_type,file', 'string', 'in:image,video,all'],
            ], [], [
                'data.*.label' => 'Field #:position Label',
                'data.*.type' => 'Field #:position Type',
                'data.*.col' => 'Field #:position Cols',
                'data.*.file_type' => 'File #:position Type',
            ]);

            $data = collect($request->data)->map(function($data) {
                $data['name'] = str($data['label'])->slug('_')->toString();
                if ($data['type'] === 'file') {
                    $data['accept'] = $this->file_types[$data['file_type']];
                }
                return $data;
            })->toJson(JSON_PRETTY_PRINT);
            $disk->put('company_verification_data.json', $data);
        }

        return $this->buildResponse([
            'data' => $data,
            'message' => $action === 'save' ? 'Company Verification Data has been updated.' : 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}
