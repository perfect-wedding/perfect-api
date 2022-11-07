<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Company;
use App\Models\v1\GiftShop;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use App\Models\v1\ShopItem;
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
            'items' => ['required_if:type,cart_checkout', 'array'],
        ]);

        $user = Auth::user();
        $code = HttpStatus::BAD_REQUEST;

        try {
            $reference = config('settings.trx_prefix', 'TRX-').$this->generate_string(20, 3);

            if ($request->type === 'verify_company') {
                $company = $user->companies()->findOrFail($request->company_id);
                $transactions = $company->transacts();
                $due = config('settings.company_verification_fee');
                if ($company->verified_data && ($company->verified_data['payment'] ?? false) === true) {
                    return $this->buildResponse([
                        'message' => __(':0 is already verified.', [$company->name]),
                        'status' => 'info',
                        'status_code' => HttpStatus::TOO_MANY_REQUESTS,
                    ]);
                }

                if ($verify = $this->requestCompanyVerification($request, null, $company, true)) {
                    if ($verify['status_code'] !== HttpStatus::ACCEPTED) {
                        return $this->buildResponse($verify);
                    }
                }
            }
            if ($request->type === 'cart_checkout') {
                $items = collect($request->items)->map(function ($item) use ($user) {
                    $quantity = $item['quantity'] ?? 1;
                    if ($item['type'] === 'inventory' || $item['type'] === 'giftshop') {
                        $query = $item['type'] === 'inventory'
                            ? Inventory::query()
                            : ShopItem::query();

                        $orderable = $query->find($item['item_id']);

                        $package = ['id' => 0];
                        $requested = $orderable;
                        $total = $orderable->price * $quantity;
                    } else {
                        $requested = $user->orderRequests()->find($item['request_id'] ?? '');
                        if (! $requested) {
                            return null;
                        }
                        $orderable = $requested->orderable;
                        $package = $orderable->offers()->find($item['package_id']) ?? ['id' => 0];
                        $total = $orderable->offerCalculator($item['package_id']) * $quantity;
                    }
                    $transaction = $orderable->transactions();
                    $item['due'] = $orderable->price;

                    return [
                        'package' => $package,
                        'quantity' => $quantity,
                        'color' => $item['color'] ?? null,
                        'destination' => $item['destination'] ?? null,
                        'orderable' => $orderable,
                        'transaction' => $transaction,
                        'request' => $requested,
                        'total' => $total,
                    ];
                })->filter(fn ($item) => $item !== null);

                $due = $items->sum('total');
            }

            $real_due = round($due * 100, 2);

            if (isset($items)) {
                $items->map(function ($item) use ($reference) {
                    $quantity = $item['quantity'];
                    $orderable = $item['orderable'];
                    $price = $orderable->price;
                    $type = $orderable instanceof Service
                        ? 'service'
                        : ($orderable instanceof ShopItem ? 'giftshop' : 'inventory');

                    $item['transaction']->create([
                        'reference' => $reference,
                        'user_id' => Auth::id(),
                        'amount' => $item['total'],
                        'method' => 'Paystack',
                        'status' => 'pending',
                        'due' => $item['total'],
                        'offer_charge' => $item['package']['id'] ? $orderable->packAmount($item['package']['id']) : $price,
                        'discount' => $item['package']['id'] ? (
                            $item['package']->type === 'discount'
                                ? (($dis = $orderable->price - $item['total']) > 0
                                ? $dis : 0.00) : 0.00
                        ) : 0.00,
                        'data' => [
                            'request_id' => $item['request']['id'] ?? $item['orderable']['id'] ?? '',
                            ($type === 'service'
                                ? 'service_id'
                                : 'item_id') => $item['orderable']['id'] ?? '',
                            ($type === 'service'
                                ? 'service_title'
                                : 'item_name') => $orderable->title ?? $orderable->name ?? '',
                            'price' => $price,
                            'quantity' => $quantity,
                            'color' => $item['color'] ?? null,
                            'destination' => $item['destination'] ?? null,
                        ],
                    ]);
                });
            } else {
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

            // Dont initialize paystack for inline transaction
            if ($request->inline) {
                $tranx = [
                    'data' => ['reference' => $reference],
                ];
                $real_due = $due;
            } else {
                $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

                $tranx = $paystack->transaction->initialize([
                    'amount' => $real_due,       // in kobo
                    'email' => $user->email,     // unique to customers
                    'reference' => $reference,   // unique to transactions
                    'callback_url' => $request->get('redirect', config('settings.frontend_link')
                        ? config('settings.frontend_link').'/payment/verify'
                        : config('settings.payment_verify_url', route('payment.paystack.verify')),
                    ),
                ]);
                $real_due = $due;
            }

            $code = 200;

            return $this->buildResponse([
                'message' => $msg ?? HttpStatus::message(HttpStatus::OK),
                'status' => 'success',
                'status_code' => $code ?? HttpStatus::OK, //202
                'payload' => $tranx ?? [],
                'transaction' => $transaction ?? [],
                'amount' => $real_due,
                'refresh' => ['user' => new UserResource($request->user()->refresh())],
            ]);
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'status_code' => $e instanceof ApiException ? HttpStatus::BAD_REQUEST : HttpStatus::SERVER_ERROR,
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
            'status_code' => HttpStatus::BAD_REQUEST,
        ];

        if (! $request->reference) {
            $process['message'] = 'No reference supplied';
        }

        try {
            if ($request->get('method') === 'wallet') {
                $tranx = new \stdClass();
                $tranx->data = new \stdClass();
                $tranx->data->status = 'failed';
                if (Auth::user()->wallet_transactions()->where('reference', $request->reference)->exists()) {
                    $tranx->data->status = 'success';
                }
            } else {
                $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
                $tranx = $paystack->transaction->verify([
                    'reference' => $request->reference,
                ]);
            }

            if ('success' === $tranx->data->status) {
                $transaction = Transaction::where('reference', $request->reference)->firstOrFail();
                if ($transaction->status !== 'pending') {
                    return $this->buildResponse([
                        'message' => 'Transaction already processed',
                        'status_code' => HttpStatus::OK,
                        'status' => 'success',
                        'payload' => $tranx->data,
                        'transaction' => $transaction,
                        'refresh' => ['user' => new UserResource($request->user()->refresh())]
                    ]);
                }
                $transactable = $transaction->transactable->first() ?? $transaction->transactable;

                if ($transaction->transactable_type === Company::class) {
                    $process = $this->requestCompanyVerification($request, $tranx, $transactable);
                    $type = 'company';
                    $status_info = [
                        'message' => __('Congratulations on the successfull enrolment of :0 on :1', [
                            $transactable->name,
                            config('settings.site_name'),
                        ]),
                        'info' => __('A conscierge personel will soon be assiged to verify and authenticate your business so you can start enjoying all the benefits of being a member of our community'),
                    ];
                    $transaction->status = 'completed';
                    $transaction->save();
                } else {// (
                    // $transactable instanceof Service ||
                    // $transactable instanceof Inventory) {

                    $type = $transaction->transactable_type === Service::class
                        ? 'service'
                        : ($transaction->transactable_type === ShopItem::class
                            ? 'giftshop'
                            : 'inventory'
                        );

                    Transaction::where('reference', $request->reference)
                        ->where('status', 'pending')->get()->map(function ($item) {
                            $type = $item->transactable_type === Service::class
                                ? 'service'
                                : ($item->transactable_type === ShopItem::class
                                    ? 'giftshop'
                                    : 'inventory'
                                );

                            if ($type === 'inventory' || $type === 'giftshop') {
                                $query = $type === 'inventory'
                                    ? Inventory::query()
                                    : ShopItem::query();

                                $orderable = $query->find($item['data']['item_id']);
                                $requested = new \stdClass();
                                $requested->company_id = $orderable->company_id;
                                $requested->due_date = now();
                                $requested->destination = $item['data']['destination'] ?? '';
                            } else {
                                $requested = auth()->user()->orderRequests()->find($item['data']['request_id']);
                                if (!$requested) {
                                    throw new \ErrorException('Invalid Request', HttpStatus::BAD_REQUEST);
                                }
                                $orderable = $requested->orderable;
                            }

                            $order = $orderable->orders()->create([
                                'user_id' => auth()->id(),
                                'company_id' => $requested->company_id,
                                'company_type' => $type === 'giftshop' ? GiftShop::class : Company::class,
                                'qty' => $item['data']['quantity'],
                                'color' => $item['data']['color'] ?? '',
                                'amount' => $item['amount'],
                                'accepted' => true,
                                'status' => 'pending',
                                'due_date' => $requested->due_date,
                                'destination' => $requested->destination,
                                'code' => $item['reference'],
                            ]);

                            if ($order && $type === 'service') {
                                $requested->delete();
                            }

                            if ($type === 'inventory' || $type === 'giftshop') {
                                $orderable->decrement('stock', $order->qty); //decrement stock
                            }

                            $item->status = 'completed';
                            $item->save();

                            return $order;
                        });

                    $process = [
                        'status' => 'success',
                        'status_code' => HttpStatus::OK,
                    ];
                    $status_info = [
                        'message' => __('Transaction completed successfully'),
                        'info' => __('Your :0 order was successfull, you can check the status of your order in your dashboard', [
                            $type === 'service' ? 'service' : 'warehouse item',
                        ]),
                        'status' => 'success',
                    ];
                }
            }
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $payload = $e instanceof ApiException ? $e->getResponseObject() : [];
            Log::error($e->getMessage(), ['url' => url()->full(), 'request' => $request->all()]);

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
            $type => ($transactable ?? new \stdClass()),
        ]), $status_info ? ['status_info' => $status_info] : null);
    }

    public function requestCompanyVerification(Request $request, $tranx, $company, $init = false)
    {
        $error = null;
        $verified_data = $init === true ? ['init' => true] : [];
        $generic_error = __('We are unable to verify the existence of your company, please update your business info and try again.');

        if ($company && (! $company->verified_data || $company->verified_data['payment'] === false)) {
            if ($init === true || 'success' === $tranx->data->status) {
                if ($company->role === 'company' && isset($company->rc_number, $company->name, $company->rc_company_type)) {
                    $verify = $this->identityPassBusinessVerification($company->rc_number, $company->name, $company->rc_company_type);
                    $verified_data = $verify['response']['data'] ?? [];
                    if (! isset($verify['status']) || $verify['status'] == false) {
                        $error = $generic_error;
                    } elseif ($verify['status'] == true && (
                        str($verified_data['company_address'] ?? '')->match("%$company->address%")->isEmpty() &&
                        str($verified_data['branchAddress'] ?? '')->match("%$company->address%")->isEmpty()
                    )
                    ) {
                        $error = __('We could not verify that your company exists at the address you provided.');
                    }
                } elseif ($company->role === 'company' && ! isset($company->rc_number, $company->name, $company->rc_company_type)) {
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

            return ['message' => $error, 'status' => 'error', 'status_code' => HttpStatus::TOO_MANY_REQUESTS];
        }

        if ($error) {
            return ['message' => $error, 'status' => 'error', 'status_code' => HttpStatus::BAD_REQUEST];
        }

        return [
            'message' => __('Verification request successfully sent.'),
            'refresh' => ['user' => new UserResource($request->user()->refresh())],
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ];
    }

    /**
     * Delete a transaction and related models
     * The most appropriate place to use this is when a user cancels a transaction without
     * completing payments, although there are limitless use cases.
     *
     * @param  Request  $request
     * @return void
     */
    public function terminateTransaction(Request $request)
    {
        $deleted = false;
        if ($transaction = Transaction::whereReference($request->reference)->where('user_id', Auth::id())->first()) {
            $transaction->delete();
            $deleted = true;
        }

        return $this->buildResponse([
            'message' => $deleted
                ? "Transaction with reference: {$request->reference} successfully deleted."
                : 'Transaction not found',
            'status' => ! $deleted ? 'info' : 'success',
            'response_code' => 200,
        ]);
    }
}