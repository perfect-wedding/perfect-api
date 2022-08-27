<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserResource;
use Illuminate\Http\Request;

class EmailPhoneVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $type = 'email')
    {
        $set_type = ($type == 'phone') ? 'phone number' : 'email address';
        $hasVerified = ($type == 'phone') ? $request->user()->hasVerifiedPhone() : $request->user()->hasVerifiedEmail();

        if ($hasVerified) {
            return $this->buildResponse([
                'refresh' => ['user' => new UserResource($request->user())],
                'message' => "Your $set_type is already verified.",
                'status' => 'success',
                'status_code' => httpStatus::OK,
            ]);
            // return redirect()->intended(RouteServiceProvider::HOME);
        }

        if ($type === 'email') {
            $request->user()->sendEmailVerificationNotification();
        }

        if ($type === 'phone') {
            $request->user()->sendPhoneVerificationNotification();
        }

        $datetime = $request->user()->last_attempt ?? now();

        $time_left = config('settings.token_lifespan', 30) - $datetime->diffInMinutes(now());
        $try_at = $datetime->addMinutes(config('settings.token_lifespan', 30));

        return $this->buildResponse([
            'message' => "Verification code has been sent to your {$set_type}.",
            'time_left' => $time_left,
            'try_at' => $try_at,
            'status' => 'success',
            'status_code' => httpStatus::OK,
        ]);
    }

    /**
     * Ping the verification notification to know the status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function ping(Request $request, $type = 'email')
    {
        $set_type = ($type == 'phone') ? 'phone number' : 'email address';
        $datetime = $request->user()->last_attempt ?? now()->subMinutes(config('settings.token_lifespan', 30) + 1);

        $time_left = config('settings.token_lifespan', 30) - $datetime->diffInMinutes(now());
        $try_at = $datetime->addMinutes(config('settings.token_lifespan', 30));
        $hasVerified = ($type == 'phone') ? $request->user()->hasVerifiedPhone() : $request->user()->hasVerifiedEmail();

        return $this->buildResponse([
            'message' => $hasVerified
                ? "We have successfully verified your $set_type, welcome to our community."
                : HttpStatus::message(HttpStatus::OK),
            $hasVerified
                ? 'refresh'
                : 'user' => $hasVerified
                ? ['user' => new UserResource($request->user())]
                : new UserResource($request->user()),
            'time_left' => $time_left,
            'try_at' => $try_at,
            'verified' => $hasVerified,
            'status' => 'success',
            'status_code' => httpStatus::OK,
        ]);
    }
}
