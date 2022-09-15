<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Market;
use App\Services\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    protected $data_type = [
        'prefered_notification_channels' => 'array',
        'keep_successful_queue_logs' => 'boolean',
        'company_verification_fee' => 'number',
        'task_completion_reward' => 'number',
        'strict_mode' => 'boolean',
        'rich_stats' => 'boolean',
        'slack_debug' => 'boolean',
        'slack_logger' => 'boolean',
        'verify_email' => 'boolean',
        'verify_phone' => 'boolean',
        'token_lifespan' => 'number',
        'use_queue' => 'boolean',
        'force_https' => 'boolean',
    ];

    public function index()
    {
        $app_data = [
            'page' => 'admin.index',
            'title' => 'Admin Dashboard',
            'vendor_page' => 'dashboard',
            'count_items' => Market::count(),
            'market_items' => Market::paginate(15),
            'categories' => Category::orderBy('priority', 'ASC')->get(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('market.shop', $app_data);
    }

    public function saveSettings(Request $request)
    {
        \Gate::authorize('can-do', ['configuration']);
        $this->validate($request, [
            'contact_address' => ['nullable', 'string'],
            'currency' => ['required', 'string'],
            'currency_symbol' => ['nullable', 'string'],
            'default_banner' => [Rule::requiredIf(fn () => ! config('settings.default_banner')), 'mimes:jpg,png'],
            'auth_banner' => [Rule::requiredIf(fn () => ! config('settings.auth_banner')), 'mimes:jpg,png'],
            'frontend_link' => ['nullable', 'string'],
            'prefered_notification_channels' => ['required', 'array'],
            'keep_successful_queue_logs' => ['nullable'],
            'site_name' => ['required', 'string'],
            'slack_debug' => ['nullable', 'boolean'],
            'slack_logger' => ['nullable', 'boolean'],
            'token_lifespan' => ['required', 'numeric'],
            'trx_prefix' => ['required', 'string'],
            'verify_email' => ['nullable', 'boolean'],
            'verify_phone' => ['nullable', 'boolean'],
        ]);

        collect($request->all())->except(['_method'])->map(function ($config, $key) use ($request) {
            if ($request->hasFile($key)) {
                (new Media)->delete('default', pathinfo(config('settings.'.$key), PATHINFO_BASENAME));
                $save_name = (new Media)->save('default', $key, $config);
                $config = (new Media)->image('default', $save_name, asset('media/default.jpg'));
            } elseif (($type = collect($this->data_type))->has($key)) {
                if (! is_array($config) && $type->get($key) === 'array') {
                    $config = valid_json($config, true, explode(',', $config));
                } elseif ($type->get($key) === 'boolean') {
                    $config = boolval($config);
                } elseif ($type->get($key) === 'number') {
                    $config = intval($config);
                }
            }
            Config::write("settings.{$key}", $config);
        });

        $settings = collect(config('settings'))
            ->except(['permissions', 'messages', 'system'])
            ->filter(fn($v, $k)=>stripos($k, 'secret') === false)
            ->mergeRecursive([
                'oauth' => [
                    'google' => collect(config('services.google'))->filter(fn($v, $k)=>stripos($k, 'secret') === false),
                    'facebook' => collect(config('services.facebook'))->filter(fn($v, $k)=>stripos($k, 'secret') === false),
                ],
            ]);

        return $this->buildResponse([
            'data' => collect($settings)->put('refresh', ['settings' => $settings]),
            'message' => 'Configuration Saved.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
