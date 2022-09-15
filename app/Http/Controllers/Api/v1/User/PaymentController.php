<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Company;
use App\Models\v1\Transaction;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class PaymentController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Initialize a paystack transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'type' => ['required', 'string'],
            'company_id' => ['required_if:type,verify_company', 'numeric', 'exists:companies,id'],
        ]);

        $user = Auth::user();
        $code = 403;

        if ($request->type === 'verify_company') {
            $company = $user->companies()->findOrFail($request->company_id);
            $transactions = $company->transacts();
            $due = config('settings.company_verification_fee');
            if ($company->verified_data && ($company->verified_data['payment'] ?? false) === true) {
                return $this->buildResponse([
                    'message' => __(":0 is already verified.", [$company->name]),
                    'status' =>  'info',
                    'status_code' => HttpStatus::TOO_MANY_REQUESTS
                ]);
            }

            if ($verify = $this->requestCompanyVerification($request, null, $company, true)) {
                if ($verify['status_code'] !== HttpStatus::ACCEPTED) {
                    return $this->buildResponse($verify);
                }
            }
        }

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $real_due = $due * 100;
            $reference = config('settings.trx_prefix', 'TRX-') . $this->generate_string(20, 3);

            // Dont initialize paystack for inline transaction
            if ($request->inline) {
                $tranx = [
                    'data' => ['reference' => $reference],
                ];
                $real_due = $due;
            } else {
                $tranx = $paystack->transaction->initialize([
                    'amount' => $real_due,       // in kobo
                    'email' => $user->email,     // unique to customers
                    'reference' => $reference,   // unique to transactions
                    'callback_url' => config('settings.frontend_link')
                        ? config('settings.frontend_link') . '/payment/verify'
                        : config('settings.payment_verify_url', route('payment.paystack.verify')),
                ]);
                $real_due = $due;

                $transactions->create([
                    'restricted' => true,
                    'user_id' => Auth::id(),
                    'reference' => $reference,
                    'method' => 'Paystack',
                    'status' => 'pending',
                    'amount' => $due,
                    'due' => $due,
                ]);
            }

            $code = 200;

            return $this->buildResponse([
                'message' => $msg ?? 'OK',
                'status' =>  'success',
                'status_code' => $code ?? 200, //202
                'payload' => $tranx ?? [],
                'transaction' => $transaction ?? [],
                'amount' => $real_due,
                'refresh' => ['user' => new UserResource($request->user()->refresh())],
            ]);
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'status_code' => 403,
                'due' => $due,
                'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Verify the paystack payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function paystackVerify(Request $request)
    {
        $type = 'company';
        $status_info = null;
        $process = [
            'message' => 'Invalid Transaction.',
            'status' => 'error',
            'status_code' => HttpStatus::FORBIDDEN,
        ];

        if (! $request->reference) {
            $process['message'] = 'No reference supplied';
        }

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $tranx = $paystack->transaction->verify([
                'reference' => $request->reference,   // unique to transactions
            ]);

            $transaction = Transaction::where('reference', $request->reference)->where('status', 'pending')->firstOrFail();

            if (($transactable = $transaction->transactable) instanceof Company) {
                $process = $this->requestCompanyVerification($request, $tranx, $transactable);
                $type = 'company';
                $status_info = [
                    'message' => __("Congratulations on the successfull enrolment of :0 on :1", [
                        $transactable->name,
                        config('settings.site_name')
                    ]),
                    'info' => __("A conscierge personel will soon be assiged to verify and authenticate your business so you can start enjoying all the benefits of being a member of our community")
                ];
            }

            $transaction->status = 'completed';
            $transaction->save();

        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $payload = $e instanceof ApiException ? $e->getResponseObject() : [];
            Log::error($e->getMessage(), ['url'=>url()->full(), 'request' => $request->all()]);

            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                'payload' => $payload,
            ]);
        }

        return $this->buildResponse(array_merge($process, [
            'payload' => $tranx ?? [],
            'type' => $type,
            $type => $transactable
        ]), $status_info ? ['status_info' => $status_info] : null);
    }

    public function requestCompanyVerification(Request $request, $tranx, $company, $init = false)
    {
        $error = null;
        $verified_data = $init === true ? ["init" => true] : [];
        $generic_error = __("We are unable to verify the existence of your company, please update your business info and try again.");

        if ($company && (!$company->verified_data || $company->verified_data['payment'] === false)) {
            if ($init === true || 'success' === $tranx->data->status) {
                if ($company->role === 'company' && isset($company->rc_number, $company->name, $company->rc_company_type)) {
                    $verify = $this->identityPassBusinessVerification($company->rc_number, $company->name, $company->rc_company_type);
                    $verified_data = $verify['response']['data'] ?? [];
                    if (!isset($verify['status']) || $verify['status'] == false) {
                        $error = $generic_error;
                    } elseif ($verify['status'] == true && (
                        str($verified_data['company_address']??'')->match("%$company->address%")->isEmpty() &&
                        str($verified_data['branchAddress']??'')->match("%$company->address%")->isEmpty()
                        )
                    ) {
                        $error = __("We could not verify that your company exists at the address you provided.");
                    }
                } elseif ($company->role === 'company' && !isset($company->rc_number, $company->name, $company->rc_company_type)) {
                    $error = $generic_error;
                }

                if ($init === true) {
                    $verified_data['payment'] = false;
                } elseif ('success' === $tranx->data->status) {
                    $verified_data['payment'] = true;
                }
                $company->verified_data = $verified_data ?? [];
                $company->save();
            }
        } elseif ($company && $init === false && 'success' === $tranx->data->status) {
            $verified_data = $company->verified_data ?? [];
            $verified_data['payment'] = true;
            $company->verified_data = $verified_data;
            $company->save();
        } elseif ($company && $company->verified_data && $company->verified_data['payment'] === true) {
            $error = __('Verification request has already been sent.');
            return [ 'message' => $error, 'status' => 'error', 'status_code' => HttpStatus::TOO_MANY_REQUESTS];
        }

        if ($error) return [ 'message' => $error, 'status' => 'error', 'status_code' => HttpStatus::BAD_REQUEST];

        return [
            'message' => __('Verification request successfully sent.'),
            'refresh' => ['user' => new UserResource($request->user()->refresh())],
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ];
    }
}