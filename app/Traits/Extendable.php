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
        $info = [
            'api' => AppInfo::basic(),
            'message' => $data['message'] ?? 'Request was successful',
            'status' => $data['status'] ?? 'success',
            'status_code' => $data['status_code'] ?? HttpStatus::OK,
        ];

        $data = collect($data)->except('message', 'status_code', 'status', 'errors', 'token', 'response_data');

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
        if ($errors) {
            $response->prepend($errors, 'errors');
        }
        if ($token) {
            $response->prepend($token, 'token');
        }
        $response->prepend($main_data, 'data');

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
                    'token' => config('settings.system.ipinfo.access_token')
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
    public function identityPassBusinessVerification(string $rc_number, string $company_name, string $company_type = 'BN') {
        $url = config('settings.system.identitypass.' . config('settings.identitypass_mode', 'sandbox'),
                    config('settings.system.identitypass.sandbox')
               );
        if ($url) {
            $url .= '/api/v2/biometrics/merchant/data/verification/cac/advance';

            $verify = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => config('settings.system.identitypass.secret_key'),
                'app-id' => config('settings.system.identitypass.app_id'),
            ])
            ->post(str_ireplace('//', '/', $url), [
                'rc_number' => $rc_number,
                'company_name' => $company_name,
                'company_type' => $company_type,
            ]);

            $response = $verify->json();
            $data = [];

            if (isset($response['detail'])) {
                $data['message'] = $response['message'] ?? $response['detail'];
            }

            if ($verify->status() === 200) {
                $data['status'] = $response['status'];
                $data['status_txt'] = $response['status'] ? 'success' : 'error';
                $data['status_code'] = HttpStatus::OK;
                $data['response'] = $response;
            } else {
                if (is_array($response['detail'])) {
                    $data['message'] = collect($response['detail'])->map(fn($f, $k)=>"$k: ".collect($f)->first())->flatten()->first();
                    $data['errors'] = $response['detail'];
                }
                $data['status'] = $response['status'] ?? false;
                $data['status_txt'] = 'error';
                $data['status_code'] = HttpStatus::BAD_REQUEST;
                $data['response'] = $response;
            }
            return $data;

            dd($url, $rc_number, $company_name, $company_type, $verify->json());
        }
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
}