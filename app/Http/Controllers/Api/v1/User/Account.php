<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CompanyResource;
use App\Http\Resources\v1\User\UserResource;
use App\Http\Resources\v1\User\WalletCollection;
use App\Models\v1\Company;
use App\Services\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Account extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (new UserResource(Auth::user()))->additional([
            'message' => 'OK',
            'status' => 'success',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function wallet()
    {
        return (new WalletCollection(Auth::user()->wallet_transactions()->orderByDesc('id')->paginate()))->additional([
            'message' => 'OK',
            'status' => 'success',
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $phone_val = stripos($request->phone, '+') !== false ? 'phone:AUTO,NG' : 'phone:'.$this->ipInfo('country');
        $set = $request->set;
        unset($request->set);

        if ($set === 'settings') {
            $this->validate($request, [
                'settings' => ['required', 'array'],
            ]);

            $user->settings = $request->settings;
        } else {
            $this->validate($request, [
                'firstname' => ['required', 'string', 'max:255'],
                'lastname' => ['required', 'string', 'max:255'],
                'phone' => ['required', $phone_val, 'max:255', Rule::unique('users')->ignore($user->id)],
                'about' => ['nullable', 'string', function ($attr, $value, $fail) {
                    if (Str::of($value)->explode(' ')->count() !== 3) {
                        $fail('Nah! Just 3 words that describe you, no more, no less.');
                    }
                }],
                'address' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
            ], [], [
                'phone' => 'Phone Number',
            ]);

            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->about = $request->about;
            $user->phone = $request->phone;
            $user->address = $request->address;
        }

        $user->save();

        return (new UserResource($user))->additional([
            'message' => 'Your profile has been successfully updated',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Update the user profile picture.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:png,jpg', 'max:350'],
        ], [
            'image.required' => 'You did not select an image for upload',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors(),
            ]);
        }
        $user->image = (new Media)->save('avatar', 'image', $user->image);
        $user->updated_at = \Carbon\Carbon::now();
        $user->save();

        return (new UserResource($user))->additional([
            'message' => 'Your profile picture has been changed successfully',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Update the user password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                'errors' => ['current_password' => ['Your current password is not correct.']],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors(),
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return (new UserResource($user))->additional([
            'message' => 'Your password has been successfully updated',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    public function updateDefaultCompany(Request $request)
    {
        $company = Company::findOrFail($request->company_id);
        $user = Auth::user();
        $user->company_id = $company->id;
        $user->save();

        return (new CompanyResource($user->company))->additional([
            'message' => "{$company->name} has been set as your default company.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
