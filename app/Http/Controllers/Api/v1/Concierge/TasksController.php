<?php

namespace App\Http\Controllers\Api\v1\Concierge;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Concierge\TasksCollection;
use App\Http\Resources\v1\Concierge\TasksResource;
use App\Models\v1\Task;
use App\Models\v1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TasksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $this->authorize('usable', 'content');
        $query = Auth::user()->tasks();
        $query->available();

        // Search and filter columns
        if ($request->search) {
            $query->whereHas('company', function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                      ->orWhere('about', 'like', "%$request->search%");
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

        return (new TasksCollection($items))->additional([
            'message' => $items->isEmpty() ? 'You do not have any pending tasks.' : HttpStatus::message(HttpStatus::OK),
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
        $query = Auth::user()
            ->tasks()
            ->completed();

        $items = ($request->limit && ($request->limit <= 0 || $request->limit === 'all'))
            ? $query->get()
            : $query->paginate($request->limit);

        return (new TasksCollection($items))->additional([
            'message' => $items->isEmpty() ? 'You have not completed any tasks.' : HttpStatus::message(HttpStatus::OK),
            'status' => $items->isEmpty() ? 'info' : 'success',
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
        $task = Auth::user()
            ->tasks()
            ->available()
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

        // Create an Event
        $task->events()->create([
            'title' => __(":0 booked for verification.", [$task->company->name]),
            'details' => __(":0 has been booked for verification, try to complete the process within the next 24 hrs.", [$task->company->name]),
            'company_id' => auth()->id(),
            'company_type' => User::class,
            'start_date' => $task->created_at,
            'end_date' => $task->ends_at,
            'duration' => $task->ends_at->diffInMinutes($task->created_at),
            'user_id' => auth()->id(),
            'location' => $task->company->full_address,
            'meta' => ['type' => 'concierge'],
            'color' => '#'.substr(md5(rand()), 0, 6),
        ]);

        return (new TasksResource($task))->additional([
            'message' => __(":0 has been booked for verification, try to complete the process within the next 24 hrs.", [$task->company->name]),
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
        $task = Auth::user()
            ->tasks()
            ->available()
            ->findOrFail($id);

        $task->status = 'released';
        $task->save();

        $company = $task->company;
        $company->status = 'unverified';
        $company->save();

        $company->verification && $company->verification->delete();

        return (new TasksResource($task))->additional([
            'message' => "{$task->company->name} has been removed from your tasks, do note that deffering tasks affects your reputation score.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}