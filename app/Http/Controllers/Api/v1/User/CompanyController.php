<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CompanyCollection;
use App\Http\Resources\v1\CompanyResource;
use App\Models\v1\Company;
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
            'state' => ['required', 'string', 'max:55'],
            'city' => ['required', 'string', 'max:55'],
            'logo' => ['required', 'image', 'mimes:jpg,png'],
            'banner' => ['required', 'image', 'mimes:jpg,png'],
        ], $rules), $messages, array_merge([
            'name' => 'Company Name',
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

        $company = Auth::user()->companies()->create($request->all());

        return (new CompanyResource($company))->additional([
            'message' => 'Company created successfully',
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
        ]);

        $company = Auth::user()->companies()->findOrFail($id);
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
        $company->save();

        return (new CompanyResource($company))->additional([
            'message' => "{$company->name} has been updated successfully.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
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
