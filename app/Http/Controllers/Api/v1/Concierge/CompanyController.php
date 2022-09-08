<?php

namespace App\Http\Controllers\Api\v1\Concierge;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CompanyCollection;
use App\Http\Resources\v1\Concierge\TasksResource;
use App\Http\Resources\v1\VerificationResource;
use App\Models\v1\Company;
use App\Models\v1\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $this->authorize('usable', 'content');
        $query = Company::where('user_id', '!=', auth()->id())
        ->where('verified_data', '!=', NULL)
        ->where('verified_data->payment', true)
        ->whereDoesntHave('verification', function($query) {
            $query->where('id', '!=', NULL);
        })->whereDoesntHave('task', function($query) {
            $query->available();
        })->whereDoesntHave('task', function($query) {
            $query->completed();
        })->whereDoesntHave('user', function($query) {
            $query->whereId(auth()->id());
        });

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

        $items = ($request->limit && ($request->limit <= 0 || $request->limit === 'all'))
            ? $query->get()
            : $query->paginate($request->limit);

        return (new CompanyCollection($items))->additional([
            'message' => $items->isEmpty() ? 'There are no tasks for now.' : HttpStatus::message(HttpStatus::OK),
            'status' => $items->isEmpty() ? 'info' : 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Verify a company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request, $task_id)
    {
        $task = Auth::user()
            ->tasks()
            ->available()
            ->find($task_id);

        if (!$task) {
            return $this->buildResponse([
                'message' => "This task is no longer available.",
                'status' => 'info',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        }
        $company = $task->company;
        $nullable = $company->verification->id ? 'nullable' : 'required';

        $this->validate($request, [
            'doc_ownerid' => [$nullable, 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'doc_invoice' => [$nullable, 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'doc_owner' => [$nullable, 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'doc_inventory' => [$nullable, 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'doc_cac' => [$nullable, 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'observations' => ['required', 'string'],
            'real_address' => ['required', 'in:true,false,0,1'],
            'exists' => ['required', 'in:true,false,0,1'],
        ], [], [
            'doc_ownerid' => 'image of owner with document ID',
            'doc_owner' => 'image of owner inside business premises',
            'doc_inventory' => 'scanned copy of the inventory of the business',
            'doc_invoice' => 'scanned copy of the stock invoice',
            'doc_cac' => 'scanned copy of the business registeration document',
        ]);

        $company->status = 'verifying';
        $company->save();

        $verification = Verification::updateOrCreate(
            ['company_id' => $company->id, 'user_id' => $company->user->id],
            [
                'status' => 'verifying',
                'concierge_id' => Auth::id(),
                'exists' => $request->exists || false,
                'observations' => $request->observations,
                'real_address' => $request->real_address || false,
            ]
        );
        $verification->save();

        $task->status = 'complete';
        $task->save();

        return (new TasksResource($task))->additional([
            'message' => "{$company->name} has been booked for verification, you will be notified once your request status changes.",
            'data' => ['verification' => new VerificationResource($verification)],
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
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
}