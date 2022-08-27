<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\EnumsAndConsts\HttpStatus;
use App\Events\PhoneVerified;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserResource;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VerifyEmailPhoneController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url').RouteServiceProvider::HOME.'?verified=1'
            );
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(
            config('app.frontend_url').RouteServiceProvider::HOME.'?verified=1'
        );
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\Request  $request
     * @return \Illuminate\Http\Response
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
                'status_code' => HttpStatus::OK,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        $code = ($type == 'email') ? $request->user()->email_verify_code : ($type == 'phone' ? $request->user()->phone_verify_code : null);

        // check if it has not expired: the time is 30 minutes and that the code is valid
        $last_attempt = ($request->user()->hasVerifiedPhone() && $request->user()->last_attempt === null) ? $request->user()->phone_verified_at : $request->user()->last_attempt;
        if ($request->code !== $code || $last_attempt->diffInMinutes(now()) >= config('settings.token_lifespan', 30)) {
            return $this->buildResponse([
                'message' => 'An error occured.',
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                'errors' => [
                    'code' => __('The code you provided has expired or does not exist.'),
                ],
            ]);
        }

        if ($type == 'email' && $request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        if ($type == 'phone' && $request->user()->markPhoneAsVerified()) {
            event(new PhoneVerified($request->user()));
        }

        return $this->buildResponse([
            'refresh' => ['user' => new UserResource($request->user())],
            'message' => "We have successfully verified your $set_type, welcome to our community.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
