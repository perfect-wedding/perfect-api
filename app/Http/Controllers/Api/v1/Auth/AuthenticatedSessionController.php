<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\UserSocialAuth;
use App\Traits\Extendable;
use DeviceDetector\DeviceDetector;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    use Extendable;

    public function index()
    {
        if ($user = Auth::user()) {
            $errors = $code = $messages = $action = null;

            return view('web-user', compact('user', 'errors', 'code', 'action'));
        }

        return view('login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LoginRequest $request)
    {
        try {
            $request->authenticate();
            $user = $request->user();

            return $this->setUserData($request, $user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                'errors' => [
                    'email' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function socialLogin(Request $request, $type = 'google')
    {
        $accessToken = $request->auth->authentication->accessToken ?? $request->auth['authentication']['accessToken'] ?? '';
        try {
            $socialUser = Socialite::driver($type)->stateless()->userFromToken($accessToken);
            $auth = UserSocialAuth::where("{$type}_token", $accessToken)
                    ->orWhere('email', $socialUser->email)
                    ->orWhere("{$type}_id", $socialUser->id);

            if ($auth->doesntExist()) {
                return $this->buildResponse([
                    'message' => 'This account has not been registered with us.',
                    'status' => 'info',
                    'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                ]);
            }

            Auth::login($auth->first()->user);

            return $this->setUserData($request, $auth->first()->user);
        } catch (ClientException|\ErrorException $e) {
            return $this->buildResponse([
                'message' => HttpStatus::message($e->getCode() > 99 ? $e->getCode() : HttpStatus::BAD_REQUEST),
                'status' => 'error',
                'status_code' => $e->getCode() > 99 ? $e->getCode() : HttpStatus::BAD_REQUEST,
            ]);
        }
    }

    public function setUserData(Request|LoginRequest $request, $user)
    {
        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();
        $token = $user->createToken($device)->plainTextToken;

        $user->window_token = md5(rand().$device.$user->username.$user->password.time());
        $user->access_data = $this->ipInfo();
        $user->save();

        return (new UserResource($user))->additional([
            'message' => 'Login was successfull',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
            'token' => $token,
            'window_token' => $user->window_token,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->user()->tokens()->delete();

        if (! $request->isXmlHttpRequest()) {
            session()->flush();

            return response()->redirectToRoute('web.login');
        }

        return $this->buildResponse([
            'message' => 'You have been successfully logged out',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}
