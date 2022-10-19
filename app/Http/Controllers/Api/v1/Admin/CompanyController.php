<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CompanyCollection;
use App\Http\Resources\v1\Business\CompanyResource;
use App\Models\v1\Company;
use Illuminate\Http\Request;
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
            'intro' => ['required', 'string', 'max:55'],
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
    public function index(Request $request)
    {
        \Gate::authorize('can-do', ['company.manage']);
        $query = Company::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                      ->orWhere('intro', 'like', "%$request->search%")
                      ->orWhere('about', 'like', "%$request->search%")
                      ->orWhere('address', 'like', "%$request->search%")
                      ->orWhere('type', $request->search)
                      ->orWhere('city', $request->search)
                      ->orWhere('state', $request->search)
                      ->orWhere('country', $request->search);
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        // Filter Items by type
        if ($request->type && in_array($request->type, ['provider', 'vendor'])) {
            $query->where('type', $request->type);
        } elseif ($request->type && in_array($request->type, ['verified', 'unverified'])) {
            $query->verified($request->type == 'verified');
        }

        $companies = $query->paginate(15)->onEachSide(1)->withQueryString();
        return (new CompanyCollection($companies))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Gate::authorize('can-do', ['company.create']);
        $this->validate($request, []);

        $company = new Company;
        $company->type = $request->type;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        \Gate::authorize('can-do', ['company.manage']);
        return (new CompanyResource($company))->additional([
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
    public function update(Request $request, Company $company)
    {
        \Gate::authorize('can-do', ['company.update']);
        $this->validate($request, [
            'name' => ['required', 'string', 'unique:companies,name,'.$company->id],
            'phone' => ['required', 'string', 'unique:companies,phone,'.$company->id],
            'email' => ['required', 'string', 'unique:companies,email,'.$company->id],
            'logo' => ['sometimes', 'image', 'mimes:jpg,png'],
            'banner' => ['sometimes', 'image', 'mimes:jpg,png'],
            'rc_number' => ['sometimes', 'string', 'unique:companies,rc_number,'.$company->id],
            'rc_company_type' => ['sometimes', 'string', 'unique:companies,rc_company_type,'.$company->id],
        ], [], [
            'phone' => __('Phone Number'),
            'rc_number' => __('Company Registeration Number'),
            'rc_company_type' => __('Company Type'),
        ]);

        $company->name = $request->name;
        $company->type = $request->type ?: $company->type;
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

        return (new CompanyResource($company))->additional([
            'message' => __(':0 has been updated successfully.', [$company->name]),
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
        \Gate::authorize('can-do', ['company.update']);
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
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    /**
     * Delete the specified company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        \Gate::authorize('can-do', ['company.delete']);
        if ($request->items) {
            $items = collect($request->items)->map(function ($item) use ($request) {
                $item = Company::whereId($item)->first();
                if ($item) {
                    $item->task()->delete();
                    $item->staff()->delete();
                    $item->services->each(function ($service) {
                        $service->offers()->delete();
                        $service->orders()->delete();
                        $service->delete();
                    });
                    $item->transacts()->delete();
                    $item->inventories()->delete();
                    $item->verification()->delete();
                    $item->transactions()->delete();
                    $item->orderRequests()->delete();
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->name : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $items->count() === 1
                    ? __(':0 has been deleted', [$items->first()])
                    : __(':0 companies have been deleted.', [$items->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Company::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->name]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
