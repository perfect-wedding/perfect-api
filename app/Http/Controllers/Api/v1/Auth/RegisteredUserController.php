<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\User;
use App\Models\v1\UserSocialAuth;
use App\Traits\Extendable;
use DeviceDetector\DeviceDetector;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Laravel\Socialite\Facades\Socialite;

class RegisteredUserController extends Controller
{
    use Extendable;

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $phone_val = stripos($request->phone, '+') !== false ? 'phone:AUTO,NG' : 'phone:'.$this->ipInfo('country');

        $validator = Validator::make($request->all(), [
            'name' => ['required_without:firstname', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => "required|$phone_val|string|max:255|unique:users",
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'address' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ], [], [
            'email' => 'Email Address',
            'phone' => 'Phone Number',
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        $user = $this->createUser($request);

        event(new Registered($user));

        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();

        $token = $user->createToken($device)->plainTextToken;
        $this->setUserData($user);

        return $this->preflight($token);
    }

    public function setUserData($user)
    {
        $user->access_data = $this->ipInfo();
        $user->save();
    }

    public function socialCreateAccount(Request $request, $type = 'google')
    {
        $accessToken = $request->auth->authentication->accessToken ?? $request->auth['authentication']['accessToken']??'';
        try {
            $socialUser = Socialite::driver($type)->stateless()->userFromToken($accessToken);

            if (User::whereEmail($socialUser->email)->exists()) {
                return $this->buildResponse([
                    'message' => "Please login with your email address, you already have an account with us",
                    'status' => 'info',
                    'status_code' => HttpStatus::TOO_MANY_REQUESTS,
                ]);
            }

            $user = $this->createUser(collect([
                'name' => $socialUser->name,
                'firstname' => str($socialUser->name)->explode(' ')->first(),
                'lastname' => str($socialUser->name)->explode(' ')->last(),
                'email' => $socialUser->email,
                'address' => $request->address,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'role' => $request->role,
                'type' => $request->type,
                'password' => Hash::make(MD5($socialUser->name.time())),
            ]));

            $user->email_verified_at = now();
            $user->save();

            $socialUserAuth = $user->social_auth()->firstOrCreate([
                "email" => $socialUser->email,
                "{$type}_id" => $socialUser->id,
                "{$type}_token" => $socialUser->token,
                "{$type}_refresh_token" => $socialUser->refreshToken,
                "{$type}_expires_at" => $socialUser->expiresIn,
            ]);

            event(new Registered($user));

            $dev = new DeviceDetector($request->userAgent());
            $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();

            $token = $user->createToken($device)->plainTextToken;
            $this->setUserData($user);

            return $this->preflight($token);

        } catch (ClientException|\ErrorException $e) {
            return $this->buildResponse([
                'message' => HttpStatus::message($e->getCode() > 99 ? $e->getCode() : HttpStatus::BAD_REQUEST),
                'status' => 'error',
                'status_code' => $e->getCode() > 99 ? $e->getCode() : HttpStatus::BAD_REQUEST,
            ]);
        }
    }

    /**
     * Create a new user based on the provided data
     *
     * @param array|object|\Illuminate\Support\Collection|Request $request
     * @return App\Models\v1\User
     */
    public function createUser($request)
    {
        $user = User::create([
            'role' => $request->get('role'),
            'type' => $request->get('type'),
            'name' => $request->get('name'),
            'firstname' => $request->get('firstname'),
            'lastname' => $request->get('lastname'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'dob' => $request->get('dob'),
            'address' => $request->get('address'),
            'country' => $request->get('country'),
            'state' => $request->get('state'),
            'city' => $request->get('city'),
            'password' => Hash::make($request->get('password')),
        ]);

        if (! config('settings.verify_email')) {
            $user->phone_verified_at = now();
            $user->save();
        }

        if (! config('settings.verify_phone')) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    /**
     * Log the newly registered user in
     *
     * @param string $token
     * @return App\Http\Resources\v1\User\UserResource
     */
    public function preflight($token)
    {
        [$id, $user_token] = explode('|', $token, 2);
        $token_data = DB::table('personal_access_tokens')->where('token', hash('sha256', $user_token))->first();
        $user_id = $token_data->tokenable_id;

        Auth::loginUsingId($user_id);

        return (new UserResource(Auth::user()))->additional([
            'message' => 'Registration was successfull',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
            'token' => $token,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }
}
