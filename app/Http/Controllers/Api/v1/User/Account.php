<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CompanyResource;
use App\Http\Resources\v1\User\TransactionResource;
use App\Http\Resources\v1\User\UserResource;
use App\Http\Resources\v1\User\WalletCollection;
use App\Models\v1\Company;
use App\Models\v1\User;
use App\Models\v1\Wallet;
use App\Rules\WordLimit;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ToneflixCode\LaravelFileable\Media;
use Yabacon\Paystack;

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

    public function profile()
    {
        $user = Auth::user();

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
        $user = Auth::user();

        return (new WalletCollection($user->wallet_transactions()->statusIs('complete')->orderByDesc('id')->paginate()))->additional([
            'wallet_bal' => $user->wallet_bal,
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

    /**
     * Request for withdrawal
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function withdrawal(Request $request)
    {
        $user = Auth::user();

        if (! $user->bank_name || ! $user->bank_account_name || ! $user->bank_account_number) {
            $has = $user->bank_name || $user->bank_account_name || $user->bank_account_number;

            return $this->buildResponse([
                'message' => __('Please :0 your bank info before requesting a withdrawal', [$has ? 'update' : 'add']),
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        $this->validate($request, [
            'amount' => ['required', 'numeric', 'min:'.config('settings.min_withdraw_amount', 2100), 'max:'.$user->wallet_bal],
        ], [
            'amount.min' => 'The minimum withdrawal amount is '.config('settings.min_withdraw_amount', 1000),
            'amount.max' => 'You do not have enough balance to withdraw this amount',
        ]);

        $detail = __('Withdrawal of :0 to :1 (:2)', [
            money($request->amount), $user->bank_account_name, $user->bank_account_number,
        ]);
        $status = config('settings.auto_approve_withdraw', false) ? 'approved' : 'pending';
        $user->useWallet('Withdrawal', $request->amount, $detail, 'withdrawal', $status);

        return response()->json([
            'refresh' => ['user' => new UserResource($user)],
            'message' => __('Your withdrawal request has been successfully submitted, you will be notified once it is processed'),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ], HttpStatus::OK);
    }

    public function fundWallet(Request $request, $action = 'create')
    {
        $user = Auth::user();

        $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
        $transactions = $user->transactions();
        if ($action === 'create') {
            $this->validate($request, [
                'amount' => [
                    'required', 'numeric', 'min:'.conf('min_funding_amount', 1000), 'max:'.conf('max_funding_amount', 10000),
                ],
            ], [
                'amount.min' => 'The minimum amount you can fund is '.money(conf('min_funding_amount', 1000)),
                'amount.max' => 'You can not add more than '.money(conf('max_funding_amount', 1000)).' to your wallet at a time',
            ]);

            $reference = config('settings.trx_prefix', 'TRX-').$this->generate_string(20, 3);
            $due = $request->amount;
            $real_due = round($due * 100, 2);
            $wallet = $user->useWallet('Direct funding', $request->amount, 'Wallet direct funding via Paystack', null, 'pending');
            $transaction = $transactions->create([
                'transactable_type' => Wallet::class,
                'transactable_id' => $wallet->id,
                'reference' => $reference,
                'amount' => $due,
                'type' => 'wallet_funding',
                'status' => 'pending',
                'description' => 'Wallet direct funding via Paystack',
                'method' => 'Paystack',
                'restricted' => false,
            ]);
            // Dont initialize paystack for inline transaction
            if ($request->inline) {
                $tranx = [
                    'data' => ['reference' => $reference],
                ];
                $real_due = $due;
            } else {
                try {
                    $tranx = $paystack->transaction->initialize([
                        'amount' => $real_due,       // in kobo
                        'email' => $user->email,     // unique to customers
                        'reference' => $reference,   // unique to transactions
                        'callback_url' => $request->get('redirect'),
                    ]);
                } catch (ApiException $e) {
                    return $this->buildResponse([
                        'message' => $e->getMessage(),
                        'status' => 'error',
                        'status_code' => HttpStatus::BAD_REQUEST,
                        'payload' => $e->getResponseObject(),
                    ]);
                }
            }
        } elseif ($action === 'verify') {
            $tranx = $paystack->transaction->verify([
                'reference' => $request->reference,
            ]);
            if ('success' === $tranx->data->status) {
                $transaction = $transactions->where('reference', $request->reference)->where('status', 'pending')->firstOrFail();
                $wallet = $transaction->transactable;
                $wallet->status = 'complete';
                $wallet->save();
                $msg = __('Your wallet was successfully funded with :0.', [money($wallet->amount)]);
                $transaction->status = 'completed';
                $transaction->save();
            } else {
                return $this->buildResponse([
                    'message' => $tranx->data->gateway_response,
                    'status' => 'error',
                    'status_code' => HttpStatus::BAD_REQUEST,
                    'payload' => $tranx->data,
                ]);
            }
        }

        return (new TransactionResource($transaction))->additional([
            'message' => $msg ?? HttpStatus::message($action === 'create' ? HttpStatus::CREATED : HttpStatus::ACCEPTED),
            'status' => 'success',
            'payload' => $tranx ?? new \stdClass(),
            'status_code' => $action === 'create' ? HttpStatus::CREATED : HttpStatus::ACCEPTED,
            'transaction' => $transaction ?? new \stdClass(),
            'amount' => $real_due ?? $transaction->amount ?? 0,
        ])->response()->setStatusCode($action === 'create' ? HttpStatus::CREATED : HttpStatus::ACCEPTED);
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

        $this->validate($request, [
            'image' => ['required', 'image', 'mimes:png,jpg', 'max:1024'],
        ], [
            'image.required' => 'You did not select an image for upload',
        ]);

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
     * Update the user profile picture.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveVerifiedData(Request $request)
    {
        $user = Auth::user();

        $this->validate($request, [
            'image' => ['required', 'image', 'mimes:png,jpg', 'max:1024'],
            'reference' => ['required', 'string', 'in:'.$user->username.'-'.$user->id],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'image.required' => 'We could not find your image',
        ]);

        // Check if the Verification-Type header is set and if it is set to 'identity-pass'
        if (base64_decode($request->header('signature')) !== config('settings.identitypass_public_key')) {
            return $this->buildResponse([
                'message' => 'You are not allowed to perform this action',
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        if (! $user->image) {
            $user->image = (new Media)->save('avatar', 'image', $user->image);
        }
        $user->firstname = $request->firstname ?? $user->firstname;
        $user->lastname = $request->lastname ?? $user->lastname;
        $user->address = $request->address ?? $user->address;
        $user->updated_at = \Carbon\Carbon::now();
        $user->markAccountAsVerified();

        $user->saveQuietly();

        return (new UserResource($user))->additional([
            'message' => 'Your account has been successfully verified.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Update the user profile picture.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function identityPassVerification(Request $request, $type = 'bvn')
    {
        $user = Auth::user();
        $data = [];
        $response = ['status' => false];

        $message = 'Your request has been received and is being reviewed, this may take up to 48 hours.
            You will be notified once your request has been processed.';

        if (isset($user->verification_data[$type])) {
            $data = $user->verification_data[$type];
        } else {
            $response = $this->identityPassUserVerification($request, $type);
        }

        if ($response['status'] === true || ! empty($data)) {
            $level = 2;
            if (empty($data)) {
                if ($type === 'bvn') {
                    $level = 1;
                    $data = [
                        'firstname' => ucwords(mb_strtolower($response['response']['bvn_data']['firstName'] ?? '')),
                        'lastname' => ucwords(mb_strtolower($response['response']['bvn_data']['lastName'] ?? '')),
                        'middlename' => ucwords(mb_strtolower($response['response']['bvn_data']['middleName'] ?? '')),
                        'gender' => $response['response']['bvn_data']['gender'] ?? '',
                        'dob' => $response['response']['bvn_data']['dateOfBirth'] ?? '',
                        'marital_status' => $response['response']['bvn_data']['maritalStatus'] ?? '',
                        'nationality' => $response['response']['bvn_data']['nationality'] ?? '',
                        'address' => $response['response']['bvn_data']['residentialAddress'] ?? '',
                        'state_of_residence' => $response['response']['bvn_data']['stateOfResidence'] ?? '',
                        'state_of_origin' => $response['response']['bvn_data']['stateOfOrigin'] ?? '',
                        'registration_date' => $response['response']['bvn_data']['registrationDate'] ?? '',
                        'nin' => $response['response']['bvn_data']['nin'] ?? '',
                        'bvn' => $response['response']['bvn_data']['bvn'] ?? '',
                        'phone_number_1' => $response['response']['bvn_data']['phoneNumber1'] ?? '',
                        'phone_number_2' => $response['response']['bvn_data']['phoneNumber2'] ?? '',
                        'face_match' => floor($response['response']['face_data']['confidence'] ?? ''),
                        'image' => (new Media)->saveEncoded('private.docs', $response['response']['bvn_data']['base64Image'] ?? null),
                        'selfie' => (new Media)->save('private.docs', 'image'),
                        'verified' => $response['response']['verification']['status'] ?? '',
                    ];
                } elseif ($type === 'drivers_license') {
                    $data = [
                        'firstname' => ucwords(mb_strtolower($response['response']['frsc_data']['firstName'] ?? '')),
                        'lastname' => ucwords(mb_strtolower($response['response']['frsc_data']['lastName'] ?? '')),
                        'middlename' => ucwords(mb_strtolower($response['response']['frsc_data']['middleName'] ?? '')),
                        'gender' => $response['response']['frsc_data']['gender'] ?? '',
                        'dob' => $response['response']['frsc_data']['birthDate'] ?? '',
                        'license_number' => $response['response']['frsc_data']['license_no'] ?? '',
                        'issued_date' => $response['response']['frsc_data']['issueDate'] ?? '',
                        'expiry_date' => $response['response']['frsc_data']['expiryDate'] ?? '',
                        'state_of_issue' => $response['response']['frsc_data']['stateOfIssue'] ?? '',
                        'image' => (new Media)->saveEncoded('private.docs', $response['response']['frsc_data']['photo'] ?? null),
                        'doc' => (new Media)->save('private.docs', 'image'),
                        'verified' => true,
                    ];
                } elseif ($type === 'vin' || $type === 'voters_card') {
                    $data = [
                        'firstname' => ucwords(mb_strtolower($response['response']['vc_data']['first_name'] ?? '')),
                        'lastname' => ucwords(mb_strtolower($response['response']['vc_data']['last_name'] ?? '')),
                        'occupation' => $response['response']['vc_data']['occupation'] ?? '',
                        'gender' => $response['response']['vc_data']['gender'] ?? '',
                        'state' => $response['response']['vc_data']['state'] ?? '',
                        'lga' => $response['response']['vc_data']['lga'] ?? '',
                        'vin' => $response['response']['vc_data']['vin'] ?? '',
                        'dob' => $response['response']['vc_data']['date_of_birth'] ?? '',
                        'doc' => (new Media)->save('private.docs', 'image'),
                        'verified' => true,
                    ];
                } elseif ($type === 'nin' || $type === 'nin_wo_face') {
                    $data = [
                        'firstname' => ucwords(mb_strtolower($response['response']['nin_data']['firstname']) ?? ''),
                        'lastname' => ucwords(mb_strtolower($response['response']['nin_data']['surname']) ?? ''),
                        'address' => $response['response']['nin_data']['residence_AdressLine1'] ?? '',
                        'state' => $response['response']['nin_data']['residence_state'] ?? '',
                        'city' => $response['response']['nin_data']['residence_Town'] ?? '',
                        'dob' => $response['response']['nin_data']['birthdate'] ?? '',
                        'gender' => $response['response']['nin_data']['gender'] ?? '',
                        'nin' => $response['response']['nin_data']['nin'] ?? '',
                        'vnin' => $response['response']['nin_data']['vnin'] ?? '',
                        'image' => (new Media)->saveEncoded('private.docs', $response['response']['nin_data']['photo'] ?? null),
                        'doc' => (new Media)->save('private.docs', 'image'),
                        'verified' => true,
                    ];
                } elseif ($type === 'national_passport') {
                    $data = [
                        'firstname' => ucwords(mb_strtolower($response['response']['data']['first_name'] ?? '')),
                        'lastname' => ucwords(mb_strtolower($response['response']['data']['last_name'] ?? '')),
                        'middlename' => ucwords(mb_strtolower($response['response']['data']['middle_name'] ?? '')),
                        'number' => $response['response']['data']['number'] ?? '',
                        'gender' => $response['response']['data']['gender'] ?? '',
                        'issued_at' => $response['response']['data']['issued_at'] ?? '',
                        'issued_date' => $response['response']['data']['issued_date'] ?? '',
                        'expiry_date' => $response['response']['data']['expiry_date'] ?? '',
                        'dob' => $response['response']['data']['dob'] ?? '',
                        'image' => (new Media)->saveEncoded('private.docs', $response['response']['data']['photo'] ?? null),
                        'doc' => (new Media)->save('private.docs', 'image'),
                        'verified' => $response['response']['verification']['status'] ?? '',
                    ];
                }
            }

            if (
                mb_strtolower($user->firstname) === mb_strtolower($data['firstname']) &&
                mb_strtolower($user->lastname) === mb_strtolower($data['lastname'])
            ) {
                $data['status'] = true;
                if ($level === 2) {
                    $user->verified = null;
                }
            } else {
                $data['status'] = 'not_matched';
                $data['verified'] = false;
                // $message = 'Your account could not be verified. Please reachout to our support team for assistance.';
            }

            // Merge the data with the existing data
            $data = collect($user->verification_data)->merge([
                $type => $data,
                'status' => $data['status'],
                'verified' => $data['verified'],
                'requested_at' => now(),
                'verified_at' => $data['verified'] ? now() : null,
            ]);

            $user->verification_level = $level;
            $user->verification_data = $data;
            if (config('settings.verification_strictness', 'srict') === 'relaxed' && $level >= 2) {
                // Automatically verify the user if the verification level is 2 and verification is relaxed
                $user->verified = $data['verified'] && ($data['status'] === true ? true : null);
            }

            if (! $user->image && $type === 'bvn') {
                $user->image = (new Media)->save('avatar', 'image', $user->image);
            }

            $user->saveQuietly();
        } else {
            return $this->buildResponse([
                'message' => $response['message'],
                'status' => 'error',
                'status_code' => HttpStatus::BAD_REQUEST,
            ]);
        }

        return (new UserResource($user))->additional([
            'type' => $type,
            'message' => $message,
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
