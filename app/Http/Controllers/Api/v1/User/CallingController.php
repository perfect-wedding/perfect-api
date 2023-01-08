<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Events\NewIncomingCall;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\CallingCollection;
use App\Http\Resources\v1\User\CallingResource;
use App\Models\v1\Call;
use App\Models\v1\User;
use App\Traits\Meta;
use Illuminate\Http\Request;

class CallingController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Call::query();

        $query->isCaller($user->id);

        if ($request->has('status')) {
            if ($request->status == 'all') {
                $query->orWhere(function ($query) use ($user) {
                    $query->isParticipant($user->id);
                });
            }

            if ($request->status == 'missed') {
                $query->orWhere(function ($query) use ($user) {
                    $query->isMissed($user->id);
                });
            }

            if ($request->status == 'rejected') {
                $query->orWhere(function ($query) use ($user) {
                    $query->isRejected($user->id);
                });
            }

            if ($request->status == 'accepted') {
                $query->orWhere(function ($query) use ($user) {
                    $query->isAccepted($user->id);
                });
            }

            if ($request->status == 'ongoing') {
                $query->orWhere(function ($query) {
                    $query->isOngoing();
                });
            }

            if ($request->status == 'no-answer') {
                $query->orWhere(function ($query) use ($user) {
                    $query->isCaller($user->id);
                    $query->isNoAnswer();
                });
            }
        }

        $calls = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 30));

        return (new CallingCollection($calls))->additional([
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
        // Initiate a call
        $user = auth()->user();

        $this->validate($request, [
            'participants' => 'required|array',
            'type' => 'nullable|in:audio,video',
            'event' => 'nullable|string|exists:events,id',
        ]);

        // Convert all the participants to integers
        $request->merge([
            'participants' => array_map('intval', $request->get('participants')),
        ]);

        $call = Call::create([
            'caller_id' => $user->id,
            'event_id' => $request->get('event'),
            'participant_ids' => array_merge($request->get('participants'), [$user->id]),
            'type' => $request->get('type', 'video'),
            'room_name' => str($user->username . '-')->append($request->get('type') . '-')->append(time())->toString(),
            'subject' => $request->get('subject', 'Call from ' . $user->fullname),
        ]);

        $this->oooPushIt($call);

        return (new CallingResource($call))->additional([
            'message' => HttpStatus::message(HttpStatus::CREATED),
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
        // Get a call
        $user = auth()->user();

        $call = Call::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->isCaller($user->id);
                $query->orWhere(function ($query) use ($user) {
                    $query->isParticipant($user->id);
                });
            })
            ->firstOrFail();

        return (new CallingResource($call))->additional([
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
    public function update(Request $request, $id)
    {
        // Update a call
        $user = auth()->user();

        $call = Call::where(function ($query) use ($id) {
            $query->where('id', $id)->orWhere('room_name', $id);
        })
            ->where(function ($query) use ($user) {
                $query->whereCallerId($user->id);
                $query->orWhereJsonContains('participant_ids', $user->id);
            })
            ->isRejected($user->id, false)
            ->isMissed($user->id, false)
            ->whereEndedAt(null)
            ->first();

        if (!$call) {
            return $this->buildResponse([
                'message' => 'The call does not exist, has been rejected or has ended.',
                'status' => 'error',
                'status_code' => HttpStatus::NOT_FOUND,
            ], HttpStatus::NOT_FOUND);
        }

        $this->validate($request, [
            'status' => 'required|in:accepted,rejected,missed,no-answer,started,ended',
        ]);

        if ($request->get('status') == 'accepted') {
            $call->accepted_participant_ids = $call->accepted_participant_ids->merge([$user->id])->unique();
            if (!$call->started_at) {
                $call->started_at = now();
            }
        } else if ($request->get('status') == 'rejected') {
            $call->rejected_participant_ids = $call->rejected_participant_ids->merge([$user->id])->unique();
        } else if ($request->get('status') == 'missed') {
            $call->missed_participant_ids = $call->missed_participant_ids->merge([$user->id])->unique();
        } else if ($request->get('status') == 'started') {
            $call->started_at = now();
        } else if ($request->get('status') == 'ended') {
            $call->ended_at = now();
        }

        $call->save();

        $this->oooPushIt($call, $request->get('status'));

        return (new CallingResource($call))->additional([
            'message' => __('You have :status the call.', ['status' => $request->get('status')]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        // Delete a call
        $user = auth()->user();

        if ($request->items) {
            $count = Call::whereIn('id', $request->items)->get()->map(function ($call) use ($user) {
                $this->delete($user, $call);
            })->filter(fn ($call) => !!$call)->count();

            return $this->buildResponse([
                'message' => "{$count} call records have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $call = $this->delete($user, $id);
        }

        return $this->buildResponse([
            'message' => 'Call deleted successfully',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }

    private function delete(User $user, Call|int $id)
    {
        if (!$id instanceof Call) {
            $call = Call::where('id', $id)
                ->where(function ($query) use ($user) {
                    $query->isCaller($user->id);
                    $query->orWhere(function ($query) use ($user) {
                        $query->isParticipant($user->id);
                    });
                })->first();
        } else {
            $call = $id;
        }

        if (!$call) {
            return false;
        }

        if ($call->participants->isEmpty()) {
            $call->delete();
        } else {
            $call->participant_ids = $call->participant_ids->filter(function ($id) use ($user) {
                return $id != $user->id;
            })->unique();
            $call->accepted_participant_ids = $call->accepted_participant_ids->filter(function ($id) use ($user) {
                return $id != $user->id;
            })->unique();
            $call->rejected_participant_ids = $call->rejected_participant_ids->filter(function ($id) use ($user) {
                return $id != $user->id;
            })->unique();
            $call->missed_participant_ids = $call->missed_participant_ids->filter(function ($id) use ($user) {
                return $id != $user->id;
            })->unique();
            $call->save();
        }

        return $call;
    }

    /**
     * Send the new notification to Pusher in order to notify users.
     *
     * @param  Call  $call
     * @return void
     */
    protected function oooPushIt(Call $call, string $status = null)
    {
        if (!$status) {
            $data = [
                'id' => $call->id,
                'caller' => $call->caller->fullname,
                'subject' => $call->subject,
                'ongoing' => $call->ongoing,
                'room_name' => $call->room_name,
            ];

            $call->participants->each(function ($participant) use ($data) {
                $data['participant'] = $participant['fullname'];
                broadcast(new NewIncomingCall($participant['id'], $data))->toOthers();
            });
        } else {
            $data = [
                'id' => $call->id,
                'status' => $status,
                'subject' => __(':participant :status the call', [
                    'participant' => auth()->user()->fullname,
                    'status' => $status,
                ]),
            ];

            broadcast(new NewIncomingCall(auth()->id(), $data))->toOthers();
        }
    }
}
