<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Market;
use App\Models\v1\Configuration;
use App\Models\v1\Image;
use App\Services\AdminStatistics;
use App\Services\ChartsPlus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ToneflixCode\LaravelFileable\Media;
use Winter\LaravelConfigWriter\ArrayFile;

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
        'auto_call_booking' => 'boolean',
        'token_lifespan' => 'number',
        'use_queue' => 'boolean',
        'auto_approve_withdraw' => 'boolean',
        'auto_payout_withdraw' => 'boolean',
        'force_https' => 'boolean',
        'call_quality' => 'number',
        'transaction_commission' => 'number',

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
        $this->authorize('can-do', ['configuration']);

        if ($request->has('type') && $request->type == 'configuration') {
            return $this->saveConfiguration($request);
        }

        $abval = (bool) $request->auth_banner ? 'mimes:jpg,png' : 'sometimes';
        $wbval = (bool) $request->welcome_banner ? 'mimes:jpg,png' : 'sometimes';
        $dbval = (bool) $request->default_banner ? 'mimes:jpg,png' : 'sometimes';

        $this->validate($request, [
            'contact_address' => ['nullable', 'string'],
            'currency' => ['required', 'string'],
            'currency_symbol' => ['nullable', 'string'],
            'default_banner' => [Rule::requiredIf(fn () => ! config('settings.default_banner')), $dbval],
            'auth_banner' => [Rule::requiredIf(fn () => ! config('settings.auth_banner')), $abval],
            'welcome_banner' => [Rule::requiredIf(fn () => ! config('settings.welcome_banner')), $wbval],
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

        $conf = ArrayFile::open(base_path('config/settings.php'));
        $data = collect($request->all())->except(['_method'])->map(function ($config, $key) use ($request, $conf) {
            $key = str($key)->replace('__', '.')->__toString();
            if ($request->hasFile($key)) {
                (new Media)->delete('default', pathinfo(config('settings.'.$key), PATHINFO_BASENAME));
                $save_name = (new Media)->save('default', $key, $config);
                $config = (new Media)->getMedia('default', $save_name, asset('media/default.jpg'));
            } elseif (($type = collect($this->data_type))->has($key)) {
                if (! is_array($config) && $type->get($key) === 'array') {
                    $config = valid_json($config, true, explode(',', $config));
                } elseif ($type->get($key) === 'boolean') {
                    $config = boolval($config);
                } elseif ($type->get($key) === 'number') {
                    $config = intval($config);
                }
            }

            $conf->set($key, $config);
            $conf->write();

            return $config;
        });

        $settings = collect(config('settings'))
            ->except(['permissions', 'messages', 'system'])
            ->filter(fn ($v, $k) => stripos($k, 'secret') === false)
            ->mergeRecursive([
                'oauth' => [
                    'google' => collect(config('services.google'))->filter(fn ($v, $k) => stripos($k, 'secret') === false),
                    'facebook' => collect(config('services.facebook'))->filter(fn ($v, $k) => stripos($k, 'secret') === false),
                ],
                'configurations' => (new Configuration())->build(),
            ])->merge($data);

        return $this->buildResponse([
            'data' => collect($settings)->put('refresh', ['settings' => $settings]),
            'message' => 'Configuration Saved.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    public function saveConfiguration(Request $request)
    {
        $this->authorize('can-do', ['configuration']);
        $configs = Configuration::all()->collect();
        $validations = $configs->mapWithKeys(function ($config) {
            $key = $config->key;
            $vals[] = 'nullable';
            if ($config->type === 'files') {
                $vals[] = 'file';
                $vals[] = 'mimes:jpg,png,jpeg,gif,mpeg,mp4,webm';
                $key = $key.'.*';
            } elseif ($config->type === 'number') {
                $vals[] = 'integer';
                $vals[] = 'max:'.($config->max ? $config->max : 999999999999);
            } else {
                $vals[] = $config->type ?? 'string';
            }

            if ($config->count && $config->type !== 'files') {
                $vals[] = 'max:'.$config->count;
            } elseif ($config->max && $config->type === 'files') {
                $vals[] = 'max:'.$config->max;
            }

            return [$key => implode('|', $vals)];
        });

        $attrs = $validations->keys()->mapWithKeys(function ($key) use ($configs) {
            $key = str($key)->remove('.*', false)->toString();

            return [$key => $configs->where('key', $key)->first()->title];
        });

        $this->validate($request, $validations->toArray(), [], $attrs->toArray());

        $configs->each(function ($config) use ($request) {
            $key = $config->key;
            $value = $request->input($key);
            if ($config->type === 'files' && $request->hasFile($key) && is_array($request->file($key))) {
                foreach ($request->file($key) as $index => $image) {
                    if (isset($config->files[$index])) {
                        $config->files[$index]->delete();
                    }
                    $config->files()->save(new Image([
                        'file' => (new Media)->save('default', $key, null, $index),
                    ]));
                }
            } elseif ($config->type === 'file' && $request->hasFile($key)) {
                $config->files()->delete();
                $config->files()->save(new Image([
                    'file' => (new Media)->save('default', $key, $config->files[0]->file ?? null),
                ]));
            } elseif ($config->type === 'file' && $request->has($key)) {
                $config->files()->delete();
            } else {
                $config->value = $value;
                $config->save();
            }
        });

        return $this->buildResponse([
            'data' => collect((new Configuration())->build())->put('refresh', ['configurations' => (new Configuration())->build()]),
            'message' => 'Configuration saved successfully',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    public function loadChartPlus(Request $request)
    {
        $this->authorize('can-do', ['dashboard']);

        $type = str($request->input('type', 'month'))->ucfirst()->camel()->toString();
        $data = (new ChartsPlus)->adminTransactionAndOrderCharts($request, $type);

        return $this->buildResponse([
            'data' => $data,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ], [
            'type' => $type,
            'duration' => $request->input('duration', 12),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadStats(Request $request)
    {
        $this->authorize('can-do', ['dashboard']);

        $interval = str($request->input('type', 'month'))->ucfirst()->camel()->toString();
        $data = (new AdminStatistics)->build($request, $interval);

        return $this->buildResponse([
            'data' => $data,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ], [
            'type' => $interval,
            'duration' => $request->input('duration', 12),
        ]);
    }
}