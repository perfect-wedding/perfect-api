<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\User;
use App\Traits\Extendable;
use DeviceDetector\DeviceDetector;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

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

        $user = User::create([
            'name' => $request->name,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'phone' => $request->phone,
            'dob' => $request->dob,
            'address' => $request->address,
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'password' => Hash::make($request->password),
        ]);

        if (! config('settings.verify_email') && ! config('settings.verify_phone')) {
            $user->email_verified_at = now();
            $user->phone_verified_at = now();
            $user->save();
        }

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
