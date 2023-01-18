<?php

namespace App\Traits;

use App\EnumsAndConsts\HttpStatus;
use App\Services\AppInfo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * Provide methods that determine how response should be generated.
 */
trait Extendable
{
    /**
     * Prepare the API response
     *
     * @param  array  $data
     * @return void
     */
    public function buildResponse($data = [], $extra_data = null)
    {
        $resp = $data['response_data'] ?? null;
        $errors = $data['errors'] ?? null;
        $token = $data['token'] ?? null;
        $error_code = $data['error_code'] ?? null;
        $info = [
            'api' => AppInfo::basic(),
            'message' => $data['message'] ?? 'Request was successful',
            'status' => $data['status'] ?? 'success',
            'status_code' => $data['status_code'] ?? HttpStatus::OK,
        ];

        $data = collect($data)->except('message', 'status_code', 'error_code', 'status', 'errors', 'token', 'response_data');

        $main_data = $data['data'] ?? $data ?? [];
        if (isset($main_data['data']['data']) && count($main_data['data']) === 1) {
            $main_data = $main_data['data']['data'] ?? [];
        }

        $response = collect($info);
        if ($extra_data) {
            if (is_array($extra_data)) {
                $response->prepend($extra_data[key($extra_data)], key($extra_data));
            } else {
                $response->prepend($extra_data, 'load');
            }
        }
        if ($resp) {
            $response->prepend($resp, 'resp');
        }
        if ($error_code) {
            $response->prepend(intval($error_code), 'error_code');
        }
        if ($errors) {
            $response->prepend($errors, 'errors');
        }
        if ($token) {
            $response->prepend($token, 'token');
        }
        $response->prepend($main_data, 'data');
        // DD( $info, $response);
        return response($response, $info['status_code']);
    }

    /**
     * Prepare the validation error.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function validatorFails(Validator $validator, $field = null)
    {
        return $this->buildResponse([
            'message' => $field ? $validator->errors()->first() : 'Your input has a few errors',
            'status' => 'error',
            'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors(),
        ]);
    }

    public function time()
    {
        return time();
    }

    /**
     * Check if this app is running on a local host
     *
     * @return bool
     */
    public function isLocalHosted(): bool
    {
        $ip = request()->ip();

        return stripos($ip, '127.0.0') !== false && env('APP_ENV') === 'local';
    }

    /**
     * Get the client IP address  or return preset IP if locally hosted
     *
     * @return void
     */
    public function ip()
    {
        $ip = request()->ip();
        if ($this->isLocalHosted()) {
            $ip = '197.210.76.68';
        }

        return $ip;
    }

    /**
     * Get the client's IP information
     *
     * @param [type] $key
     * @return void
     */
    public function ipInfo($key = null)
    {
        $info['country'] = 'US';
        if (($user = Auth::user()) && $user->access_data) {
            $info = $user->access_data;
        } else {
            if (config('settings.system.ipinfo.access_token') && config('settings.collect_user_data', true)) {
                $ipInfo = \Illuminate\Support\Facades\Http::get('ipinfo.io/'.$this->ip(), [
                    'token' => config('settings.system.ipinfo.access_token'),
                ]);
                if ($ipInfo->status() === 200) {
                    $info = $ipInfo->json() ?? $info;
                }
            }
        }

        return $key ? ($info[$key] ?? '') : $info;
    }

    /**
     * Verify a business using iddentity pass
     *
     * @param [type] $key
     * @return void
     */
    public function identityPassUserVerification($data, $type = 'bvn')
    {
        $url = config(
            'settings.system.identitypass.'.config('settings.identitypass_mode', 'sandbox'),
            config('settings.system.identitypass.sandbox')
        );

        $client = \Illuminate\Support\Facades\Http::withHeaders($this->idPassHeaders());

        $image = in_array($type, ['nin', 'bvn_w_face', 'bvn']) && $data->hasfile('image')
            ? base64_encode(file_get_contents($data->file('image')))
            : null;

        try {
            if ($type === 'bvn' && $url) {
                $url .= '/api/v2/biometrics/merchant/data/verification/bvn_w_face';

                // Make request to IdentityPass via laravel http client
                $request = $client->post($url, [
                    'number' => $data['number'],
                    'image' => $image,
                ]);
            } elseif (str($type)->contains(['drivers_license', 'nin', 'nin_wo_face', 'voters_card', 'national_passport']) && $url) {
                $path = in_array($type, ['nin']) ? "{$type}/image" : $type;
                // $path = $type;

                $url .= "/api/v2/biometrics/merchant/data/verification/{$path}";

                // Make request to IdentityPass via laravel http client
                $params = in_array($type, ['nin']) ? ['image' => $image] : [];

                if ($data['data'] && is_array($data['data'])) {
                    // Pass all the data as individual fields
                    foreach ($data['data'] as $key => $value) {
                        $params[$key] = $value;
                    }
                }

                $request = $client->post($url, $params);
            }

            if (isset($request)) {
                $type_name = str($type)->replace(['_wo_face', '', '_'], ['', ' ']);

                return $this->idPassResponse($request, "We could not verify your {$type_name}, please check and try again at a later time.");
            }
        } catch (\Throwable $th) {
            $error = 'Timeout: An error occurred while trying to verify your '.str_replace('_', ' ', $type).', please try again later.';
        }

        return ['message' => $error ?? 'Invalid request: '.$type, 'status' => false, 'status_code' => HttpStatus::BAD_REQUEST];
    }

    /**
     * Verify a business using iddentity pass
     *
     * @param [type] $key
     * @return \Illuminate\Support\Collection<\TKey, \TValue>|void
     */
    public function identityPassBusinessVerification(string $rc_number, string $company_name, string $company_type = 'BN')
    {
        $url = config(
            'settings.system.identitypass.'.config('settings.identitypass_mode', 'sandbox'),
            config('settings.system.identitypass.sandbox')
        );
        if ($url) {
            $url .= '/api/v2/biometrics/merchant/data/verification/cac/advance';

            $request = \Illuminate\Support\Facades\Http::withHeaders($this->idPassHeaders())
                ->post(str_ireplace('//', '/', $url), [
                    'rc_number' => $rc_number,
                    'company_name' => $company_name,
                    'company_type' => $company_type,
                ]);

            return $this->idPassResponse($request);
        }

        return ['message' => 'Invalid request', 'status' => false, 'status_code' => HttpStatus::BAD_REQUEST];
    }

    protected function idPassResponse($request, $error = 'An error occured')
    {
        $response = $request->json();
        $data = ['message' => $error];

        if (isset($response['detail']) || isset($response['message'])) {
            $data['message'] = $response['detail'] ?? $response['message'];
        }

        if ($request->status() === 200) {
            $data['status'] = $response['status'];
            $data['status_txt'] = $response['status'] ? 'success' : 'error';
            $data['status_code'] = HttpStatus::OK;
            $data['response'] = $response;
        } else {
            if (is_array($data['message'])) {
                $data['message'] = collect($data['message'])->map(fn ($f, $k) => "$k: ".collect($f)->first())->flatten()->first();
                $data['errors'] = $response['detail'] ?? $response['message'];
            }
            $data['status'] = $response['status'] ?? false;
            $data['status_txt'] = 'error';
            $data['status_code'] = HttpStatus::BAD_REQUEST;
            $data['response'] = collect($response)->map(function ($v, $k) {
                if (is_array($v)) {
                    return collect($v);
                }

                return $v;
            });
        }

        return collect($data);
    }

    /**
     * Get the headers for the request
     *
     * @param  array  $extra
     * @return array
     */
    protected function idPassHeaders($extra = [])
    {
        if (config('settings.identitypass_mode', 'sandbox') === 'sandbox') {
            $headers = [
                'x-api-key' => config('settings.system.identitypass.sandbox_keys.secret_key'),
                'app-id' => config('settings.system.identitypass.sandbox_keys.app_id'),
            ];
        } else {
            $headers = [
                'x-api-key' => config('settings.system.identitypass.secret_key'),
                'app-id' => config('settings.system.identitypass.app_id'),
            ];
        }

        return array_merge($headers, $extra);
    }

    public function uriQuerier(string|array $query): array
    {
        $parsed = [];
        if (is_array($query)) {
            $parsed = http_build_query($query);
        }

        parse_str($parsed, $output);

        return $output;
    }

    public function parseConversationId($conversation_id, $encode = false)
    {
        if (! $conversation_id) {
            return $conversation_id;
        }

        if ($encode) {
            return base64url_encode($conversation_id);
        }

        if (is_numeric($conversation_id)) {
            return $conversation_id;
        } else {
            $encoded = base64url_decode($conversation_id);
            $conversation_id = str($encoded)->explode('-')->last();
        }

        return $conversation_id;
    }
}
