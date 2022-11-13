<?php

namespace App\Http\Controllers\Api\v1\Admin\Home;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Home\TeamCollection;
use App\Http\Resources\v1\Home\TeamResource;
use App\Models\v1\Home\HomepageTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomepageTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = HomepageTeam::query();

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('name', $request->search);
                $query->orWhere('role', $request->search);
                $query->orWhere('info', $request->search);
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

        return (new TeamCollection($query->paginate()))->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'name' => ['required', 'string', 'min:3'],
            'role' => ['required', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'info' => ['required', 'string', 'min:10', 'max:90'],
            'socials' => ['nullable', 'array'],
            'template' => ['nullable', 'string', 'in:TeamContainer'],
        ]);

        $content = new HomepageTeam([
            'name' => $request->name,
            'role' => $request->role,
            'info' => $request->info,
            'socials' => $request->socials ?? [],
            'template' => $request->template ?? 'TeamContainer',
        ]);
        $content->save();

        return (new TeamResource($content))->additional([
            'message' => 'New team member added successfully',
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
    public function show(HomepageTeam $team)
    {
        $this->authorize('can-do', ['website']);

        return (new TeamResource($team))->additional([
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
    public function update(Request $request, HomepageTeam $team)
    {
        $this->authorize('can-do', ['website']);
        $this->validate($request, [
            'name' => ['required', 'string', 'min:3'],
            'role' => ['required', 'string', 'min:3'],
            'image' => ['nullable', 'mimes:jpg,png'],
            'info' => ['required', 'string'],
            'socials' => ['nullable', 'array'],
            'template' => ['nullable', 'string', 'in:TeamContainer'],
        ]);

        $team->name = $request->name;
        $team->role = $request->role;
        $team->socials = $request->socials ?? [];
        $team->info = $request->info;
        $team->template = $request->template ?? 'TeamContainer';
        $team->save();

        return (new TeamResource($team))->additional([
            'message' => "\"{$team->name}\" has been updated successfully",
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['website']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($id) {
                $team = HomepageTeam::find($id);
                if ($team) {
                    return $team->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} team members have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        } else {
            $team = HomepageTeam::findOrFail($id);
        }

        if ($team) {
            $team->delete();

            return $this->buildResponse([
                'message' => "{$team->name} has been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::OK,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested team no longer exists.',
            'status' => 'error',
            'status_code' => HttpStatus::NOT_FOUND,
        ]);
    }
}
