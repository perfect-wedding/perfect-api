<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\v1\PasswordCodeResets;
use App\Models\v1\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        // find the code
        $code = PasswordCodeResets::firstWhere('code', $request->code);

        // check if it has not expired: the time is 30 minutes
        if (! $code || $code->created_at->diffInMinutes(now()) >= 30) {
            $code && $code->delete();

            return $this->buildResponse([
                'message' => 'An error occured.',
                'status' => 'error',
                'status_code' => 422,
                'errors' => [
                    'code' => __('The code you provided has expired or does not exist.'),
                ],
            ]);
        }

        // find user's email
        $user = User::firstWhere('email', $code->email);

        // update user password Hash::make($request->password)
        $user->update(['password' => Hash::make($request->password)]);

        // delete current code
        $code->delete();

        return $this->buildResponse([
            'message' => __('Your password has successfully been chaged.'),
            'status' => 'success',
            'status_code' => 200,
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            return $this->buildResponse([
                'message' => 'An error occured.',
                'status' => 'error',
                'status_code' => 422,
                'errors' => [
                    'email' => __($status),
                ],
            ]);
        }

        return $this->buildResponse([
            'message' => __($status),
            'status' => 'success',
            'status_code' => 200,
        ]);
    }

    /**
     * Handle an incoming check password request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        // find the code
        $code = PasswordCodeResets::firstWhere('code', $request->code);

        // check if it has not expired: the time is 30 minutes
        if (! $code || $code->created_at->diffInMinutes(now()) >= 30) {
            $code && $code->delete();

            return $this->buildResponse([
                'message' => 'An error occured.',
                'status' => 'error',
                'status_code' => 422,
                'errors' => [
                    'code' => __('The code you provided has expired or does not exist.'),
                ],
            ]);
        }

        return $this->buildResponse([
            'message' => __('Your reset code is valid, you can change your password now.'),
            'status' => 'success',
            'status_code' => 200,
        ]);
    }
}