<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CompanyCollection;
use App\Http\Resources\v1\CompanyResource;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Company;
use App\Models\v1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        Validator::make($request->all(), array_merge([
            'name' => ['required', 'string', 'unique:companies,name'],
            'phone' => ['required', 'string', 'unique:companies,phone'],
            'email' => ['required', 'string', 'unique:companies,email'],
            'type' => ['required', 'string', 'in:provider,vendor'],
            'postal' => ['required', 'string', 'max:7'],
            'intro' => ['required', 'string', 'max:45'],
            'about' => ['nullable', 'string', 'min:15'],
            'address' => ['required', 'string', 'max:55'],
            'country' => ['required', 'string', 'max:55'],
            'rc_number' => ['required', 'string', 'unique:companies,rc_number'],
            'rc_company_type' => ['required', 'string', 'unique:companies,rc_company_type'],
            'state' => ['required', 'string', 'max:55'],
            'city' => ['required', 'string', 'max:55'],
            'logo' => ['required', 'image', 'mimes:jpg,png'],
            'banner' => ['required', 'image', 'mimes:jpg,png'],
        ], $rules), $messages, array_merge([
            'name' => 'Company Name',
            'phone' => 'Phone Number',
            'email' => 'Email Address'
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
            'message' => 'OK',
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

        $user = User::find(Auth::id());
        $company = $user->companies()->create($request->all());

        if (!$user->company) {
            $user->company_id = $company->id;
            $user->save();
        }

        return (new CompanyResource($company))->additional([
            'message' => 'Company created successfully',
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
        ], [], ['phone' => 'Phone Numaber']);

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

        if (!$user->company) {
            $user->company_id = $company->id;
            $user->save();
        }

        $additional = [
            'message' => "{$company->name} has been updated successfully.",
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
            'message' => "{$company->name} $type image has been updated successfully.",
            'refresh' => ['user' => new UserResource($request->user())],
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
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
}