<?php

namespace App\Http\Controllers\Api\v1\Admin\Concierge;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Concierge\TasksCollection;
use App\Http\Resources\v1\Concierge\TasksResource;
use App\Models\v1\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TasksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $status = 'pending')
    {
        $this->authorize('can-do', ['concierge.task']);
        $query = Task::query();

        if ($status == 'pending') {
            $query->available(true, $request->user()->role === 'admin');
        } elseif ($status == 'completed') {
            $query->completed(true, $request->user()->role === 'admin');
        } elseif ($status == 'approved') {
            $query->approved();
        } elseif ($status == 'verifying') {
            $query->verifying();
        }

        // Search and filter columns
        if ($request->search) {
            $query->whereHas('company', function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                      ->orWhere('about', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && $request->order === 'latest') {
            $query->latest();
        } elseif ($request->order && $request->order === 'oldest') {
            $query->oldest();
        } elseif ($request->order && is_array($request->order)) {
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
            : $query->paginate($request->limit)->withQueryString();

        return (new TasksCollection($items))->additional([
            'message' => $items->isEmpty() ? __('There are no :0 tasks.', [$status]) : HttpStatus::message(HttpStatus::OK),
            'status' => $items->isEmpty() ? 'info' : 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Shows all completed tasks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function completed(Request $request)
    {
        $this->authorize('can-do', ['concierge.task']);
        $query = Task::query()->completed();

        $items = ($request->limit && ($request->limit <= 0 || $request->limit === 'all'))
            ? $query->get()
            : $query->paginate($request->limit)->withQueryString();

        return (new TasksCollection($items))->additional([
            'message' => $items->isEmpty() ? 'There are no completed any tasks.' : HttpStatus::message(HttpStatus::OK),
            'status' => $items->isEmpty() ? 'info' : 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Shows all completed tasks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, Task $task)
    {
        $this->authorize('can-do', ['concierge.task']);
        $this->validate($request, [
            'status' => 'required|in:approved,rejected',
            'reason' => 'required_if:status,rejected',
        ]);

        if ($request->status === 'approved') {
            $conscierge = $task->concierge;
            $conscierge->useWallet('Task', config('settings.task_completion_reward', 100), "Task completed: {$task->company->name}");
        }

        $task->status = $request->status === 'approved' ? 'approved' : 'pending';
        $task->save();

        $company = $task->company;
        $company->status = $request->status === 'approved' ? 'verified' : 'pending';
        $company->save();

        $verification = $company->verification;
        $verification->status = $request->status === 'approved' ? 'verified' : 'rejected';
        $verification->reason = $request->reason ?? null;
        $verification->save();

        return (new TasksResource($task))->additional([
            'message' => "Task has been {$request->status}.",
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->authorize('can-do', ['concierge.task']);
        $task = Task::query()
            ->available(true, true)
            ->findOrFail($id);

        return (new TasksResource($task))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Create a new task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'company_id' => ['required', 'exists:companies,id'],
        ], [], [
            'company_id' => 'Business',
        ]);

        if (Task::whereCompanyId($request->company_id)->locked()->exists()) {
            return $this->buildResponse([
                'message' => 'This task is no longer available, try finding another one.',
                'status' => 'error',
                'status_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        }

        $task = Auth::user()->tasks()->create([
            'company_id' => $request->company_id,
            'ends_at' => now()->addHours(24),
            'status' => 'pending',
        ]);

        return (new TasksResource($task))->additional([
            'message' => "{$task->company->name} has been booked for verification, try to complete the process within the next 24 hrs.",
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('can-do', ['concierge.task']);
        $task = Task::query()
            ->available()
            ->findOrFail($id);

        $task->status = 'released';
        $task->save();

        $company = $task->company;
        $company->status = 'unverified';
        $company->save();

        $company->verification && $company->verification->delete();

        return (new TasksResource($task))->additional([
            'message' => "{$task->company->name} has been removed, do note that removing tasks may affect the concierge's reputation score.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}