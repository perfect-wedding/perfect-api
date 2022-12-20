<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ReviewCollection;
use App\Http\Resources\v1\User\UserCollection;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request  $request)
    {
        $this->authorize('can-do', ['users.list']);
        $query = User::query();

        // Search and filter columns
        if ($request->search) {
            $query = User::search($request->search);
            // $query->where(function ($query) use ($request) {
            //     // Concatenate first name and last name
            //     $query->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$request->search}%"]);
            //     $query->orWhere('username', 'LIKE', "%{$request->search}%");
            //     $query->orWhere('email', $request->search);
            //     $query->orWhere('phone', $request->search);
            //     $query->orWhere('address', 'LIKE', "%{$request->search}%");

            // });
        }

        // $query->where('hidden', false);

        if (!$request->search) {
            if ($request->role ) {
                $query->where(function($query) use ($request) {
                    $query->where('role', $request->role);
                    $query->orWhereHas('company', function($tq) use ($request) {
                        $tq->where('type', $request->role)->verified();
                    });
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
        }

        $users = $query->paginate(15)->onEachSide(0)->withQueryString();

        return (new UserCollection($users))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\v1\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('can-do', ['users.manage']);

        return (new UserResource($user))->additional([
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
    public function update(Request $request, User $user)
    {
        $this->authorize('can-do', ['users.update']);

        $phone_val = stripos($request->phone, '+') !== false ? 'phone:AUTO,NG' : 'phone:'.$this->ipInfo('country');
        $this->validate($request, [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', $phone_val, 'max:255', Rule::unique('users')->ignore($user->id)],
            'about' => ['nullable', 'string', 'max:155'],
            'intro' => ['nullable', 'string', new WordLimit(3)],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
        ], [], [
            'phone' => 'Phone Number',
        ]);

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->password = $request->password ? Hash::make($request->password) : $user->password;
        $user->phone = $request->phone;
        $user->about = $request->about;
        $user->intro = $request->intro;
        $user->address = $request->address;
        $user->country = $request->country;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->save();
        $message = __(':0\'s profile has been successfully updated', [$user->fullname]);

        return (new UserResource($user))->additional([
            'message' => $message,
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['users.delete']);
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($request) {
                $item = User::find($item);
                if ($item) {
                    $delete = $this->deleteUser($item);

                    return count($request->items) === 1 ? $item->title : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $count->count() === 1
                    ? __(':0 has been deleted', [$count->first()])
                    : __(':0 users have been deleted.', [$count->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = User::findOrFail($id);
            $this->deleteUser($item);

            return $this->buildResponse([
                'message' => __(':0 has been deleted.', [$item->title]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }

    protected function deleteUser(User $user)
    {
        $user->companies()->delete();
        $user->orders()->delete();
        $user->albums()->delete();
        $user->boards()->delete();
        $user->events()->delete();
        $user->delete();

        return $user;
    }
}
