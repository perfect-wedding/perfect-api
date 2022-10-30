<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CompanyResource;
use App\Http\Resources\v1\User\UserResource;
use App\Http\Resources\v1\User\WalletCollection;
use App\Models\v1\Company;
use App\Models\v1\User;
use App\Rules\WordLimit;
use App\Services\Media;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Account extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (new UserResource(Auth::user()))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    public function profile(User $user)
    {
        return (new UserResource($user))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function wallet()
    {
        return (new WalletCollection(Auth::user()->wallet_transactions()->orderByDesc('id')->paginate()))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $field = null)
    {
        $user = Auth::user();
        $set = $request->set;
        unset($request->set);

        if ($set === 'settings') {
            $this->validate($request, ['settings' => ['required', 'array']]);
            $user->settings = $request->settings;
            $message = __('Account settings updated');
        } elseif ($set === 'status_message') {
            $this->validate($request, ['status_message' => ['required', 'string', new WordLimit(5, ['>' => 5, '<' => 3])]]);
            $user->status_message = $request->status_message;
            $message = __('Status message successfully updated');
        } else {
            $phone_val = stripos($request->phone, '+') !== false ? 'phone:AUTO,NG' : 'phone:'.$this->ipInfo('country');
            $this->validate($request, [
                'firstname' => ['required', 'string', 'max:255'],
                'lastname' => ['required', 'string', 'max:255'],
                'phone' => ['required', $phone_val, 'max:255', Rule::unique('users')->ignore($user->id)],
                'about' => ['nullable', 'string', 'max:155'],
                'intro' => ['nullable', 'string', new WordLimit(3)],
                'address' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
            ], [], [
                'phone' => 'Phone Number',
            ]);

            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->about = $request->about;
            $user->intro = $request->intro;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $message = __('Your profile has been successfully updated');
        }

        $user->save();

        return (new UserResource($user))->additional([
            'message' => $message,
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Update the user bank info.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateBank(Request $request)
    {
        $user = Auth::user();
        $this->validate($request, [
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:12'],
        ]);

        $user->bank_name = $request->bank_name;
        $user->bank_account_name = $request->bank_account_name;
        $user->bank_account_number = $request->bank_account_number;
        $user->save();

        return (new UserResource($user))->additional([
            'refresh' => ['user' => new UserResource($user)],
            'message' => 'Your bank info has been successfully updated',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    public function withdrawal(Request $request)
    {
        $user = Auth::user();

        if (!$user->bank_name || !$user->bank_account_name || !$user->bank_account_number) {
            $has = $user->bank_name || $user->bank_account_name || $user->bank_account_number;
            return $this->buildResponse([
                'message' => __("Please :0 your bank info before requesting a withdrawal", [$has ? 'update' : 'add']),
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        $this->validate($request, [
            'amount' => ['required', 'numeric', 'min:' . config('settings.min_withdraw_amount', 2100), 'max:' . $user->wallet_bal],
        ], [
            'amount.min' => 'The minimum withdrawal amount is ' . config('settings.min_withdraw_amount', 1000),
            'amount.max' => 'You do not have enough balance to withdraw this amount',
        ]);

        $user->wallet_transactions()->create([
            'reference' => config('settings.trx_prefix', 'TRX-') . $this->generate_string(20, 3),
            'amount' => $request->amount,
            'type' => 'withdrawal',
            'source' => 'Withdrawal',
            'detail' => __("Withdrawal of :0 to :1 (:2)", [
                money($request->amount), $user->bank_account_name, $user->bank_account_number
            ]),
        ]);

        return response()->json([
            'refresh' => ['user' => new UserResource($user)],
            'message' => __('Your withdrawal request has been successfully submitted, you will be notified once it is processed'),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ], HttpStatus::OK);
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
            'image' => ['required', 'image', 'mimes:png,jpg', 'max:1024'],
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
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
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
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    public function updateDefaultCompany(Request $request)
    {
        $company = Company::findOrFail($request->company_id);
        $user = Auth::user();
        $user->company_id = $company->id;
        $user->save();

        return (new CompanyResource($user->company))->additional([
            'refresh' => ['user' => new UserResource($user)],
            'message' => "{$company->name} has been set as your default company.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
