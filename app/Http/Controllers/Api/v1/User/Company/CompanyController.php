<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CompanyCollection;
use App\Http\Resources\v1\Business\CompanyResource;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Company;
use App\Models\v1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $company = Rule::requiredIf(fn () => $request->role === 'company' || (! $request->role && $request->user()->type === 'company'));

        Validator::make($request->all(), array_merge([
            'name' => ['required', 'string', 'unique:companies,name'],
            'phone' => ['required', 'string', 'unique:companies,phone'],
            'email' => ['required', 'string', 'unique:companies,email'],
            'type' => ['required', 'string', 'in:provider,vendor'],
            'role' => ['required', 'string', 'in:company,individual'],
            'postal' => ['required', 'string', 'max:7'],
            'intro' => ['required', 'string', 'max:45'],
            'about' => ['nullable', 'string', 'min:15'],
            'address' => ['required', 'string', 'max:55'],
            'country' => ['required', 'string', 'max:55'],
            'rc_number' => [$company, 'string', 'unique:companies,rc_number'],
            'rc_company_type' => [$company, 'string'],
            'state' => ['required', 'string', 'max:55'],
            'city' => ['required', 'string', 'max:55'],
            'logo' => ['sometimes', 'image', 'mimes:jpg,png'],
            'banner' => ['sometimes', 'image', 'mimes:jpg,png'],
        ], $rules), $messages, array_merge([
            'name' => __('Company Name'),
            'phone' => __('Phone Number'),
            'email' => __('Email Address'),
            'rc_number' => __('Company Registeration Number'),
            'rc_company_type' => 'Company Type',
        ], $customAttributes))->validate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Auth::user()->companies()->paginate();

        return (new CompanyCollection($companies))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, []);
        // $error = null;
        // $generic_error = __("We are unable to verify the existence of your company, please check check your submission and try again.");

        // if ($request->get('role') === 'company' && isset($request->rc_number, $request->name, $request->rc_company_type)) {
        //     $verify = $this->identityPassBusinessVerification($request->rc_number, $request->name, $request->rc_company_type);
        //     $verified_data = $verify['response']['data'] ?? [];
        //     if (!isset($verify['status']) || $verify['status'] == false) {
        //         $error = $generic_error;
        //     } elseif ($verify['status'] == true && (
        //         str($verified_data['company_address']??'')->match("%$request->address%")->isEmpty() &&
        //         str($verified_data['branchAddress']??'')->match("%$request->address%")->isEmpty()
        //         )
        //     ) {
        //         $error = __("We could not verify that your company exists at the given address.");
        //     }
        // } elseif ($request->role === 'company' && !isset($request->rc_number, $request->name, $request->rc_company_type)) {
        //     $error = $generic_error;
        // }

        // if ($error) return $this->buildResponse([ 'message' => $error, 'status' => 'error', 'status_code' => HttpStatus::BAD_REQUEST]);

        $user = User::find(Auth::id());
        // $request->merge(['verified_data' => $verified_data ?? []]);
        $company = $user->companies()->create($request->all());

        if (! $user->company) {
            $user->company_id = $company->id;
            $user->save();
        }

        return (new CompanyResource($company))->additional([
            'message' => __('Company created successfully'),
            'refresh' => ['user' => new UserResource($user->refresh())],
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $company = Auth::user()->companies()->findOrFail($id);

        return (new CompanyResource($company))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
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
        $this->validate($request, [
            'name' => ['required', 'string', 'unique:companies,name,'.$id],
            'phone' => ['required', 'string', 'unique:companies,phone,'.$id],
            'email' => ['required', 'string', 'unique:companies,email,'.$id],
            'logo' => ['sometimes', 'image', 'mimes:jpg,png'],
            'banner' => ['sometimes', 'image', 'mimes:jpg,png'],
            'rc_number' => ['sometimes', 'string', 'unique:companies,rc_number,'.$id],
            'rc_company_type' => ['sometimes', 'string', 'unique:companies,rc_company_type,'.$id],
        ], [], [
            'phone' => __('Phone Number'),
            'rc_number' => __('Company Registeration Number'),
            'rc_company_type' => __('Company Type'),
        ]);

        $user = User::find(Auth::id());

        $company = $user->companies()->findOrFail($id);
        $company->name = $request->name;
        $company->type = $request->type;
        $company->email = $request->email;
        $company->phone = $request->phone;
        $company->intro = $request->intro;
        $company->about = $request->about;
        $company->country = $request->country;
        $company->state = $request->state;
        $company->city = $request->city;
        $company->postal = $request->postal;
        $company->address = $request->address;
        $company->rc_number = $request->rc_number ?: $company->rc_number;
        $company->rc_company_type = $request->rc_company_type ?: $company->rc_company_type;
        $company->save();

        if (! $user->company) {
            $user->company_id = $company->id;
            $user->save();
        }

        $additional = [
            'message' => __(':0 has been updated successfully.', [$company->name]),
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ];

        if ($user->company_id === $company->id) {
            $additional['refresh'] = ['user' => new UserResource($user->refresh())];
        }

        return (new CompanyResource($company))->additional($additional);
    }

    /**
     * Update the specified display picture.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changeDp(Request $request, Company $company, $type = '---')
    {
        Validator::make($request->all(), [
            $type => ['required', 'image', 'mimes:jpg,png'],
        ], [], [
            $type => $type.' image',
        ])->validate();
        if (in_array($type, ['logo', 'banner'])) {
            $company->{$type} = $request->{$type};
            $company->save();
        }

        return (new CompanyResource($company))->additional([
            'message' => __(':0 :1 image has been updated successfully.', [$company->name, $type]),
            'refresh' => ['user' => new UserResource($request->user())],
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Delete a transaction and related models
     * The most appropriate place to use this is when a user cancels a transaction without
     * completing payments, although there are limitless use cases.
     *
     * @param  Request  $request
     * @return void
     */
    public function destroy(Request $request)
    {
        $deleted = false;
        if ($transaction = Auth::user()->transactions->whereReference($request->reference)->first()) {
            $transaction->delete();
            $deleted = true;
        }

        return $this->buildResponse([
            'message' => $deleted ? "Transaction with reference: {$request->reference} successfully deleted." : 'Transaction not found',
            'status' => $deleted ? 'success' : 'info',
            'status_code' => $deleted ? HttpStatus::ACCEPTED : HttpStatus::NOT_FOUND,
        ]);
    }
}
