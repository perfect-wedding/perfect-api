<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\v1\PasswordCodeResets;
use App\Models\v1\User;
use App\Notifications\SendCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users'],
        ], [
            'email.exists' => 'We could not find a user with this email address.',
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        // Delete the old code
        PasswordCodeResets::whereEmail($request->email)->delete();

        // generate the new code
        $reset = new PasswordCodeResets;
        $reset->email = $request->email;
        $reset->code = mt_rand(100000, 999999);
        $reset->save();

        // Notify the user
        User::whereEmail($reset->email)->first()->notify(new SendCode($reset->code));

        // And finally return a response
        return $this->buildResponse([
            'message' => __('We have sent you a message to help with recovering your password.'),
            'status' => 'success',
            'response_code' => 200,
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            return $this->buildResponse([
                'message' => 'An error occured.',
                'status' => 'error',
                'response_code' => 422,
                'errors' => [
                    'email' => __($status),
                ],
            ]);
        }

        return $this->buildResponse([
            'message' => __($status),
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}
