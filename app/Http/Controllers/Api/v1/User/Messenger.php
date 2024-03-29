<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Events\MessageWasComposed;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\Messenger\ConversationCollection;
use App\Http\Resources\v1\User\Messenger\ConversationResource;
use App\Http\Resources\v1\User\Messenger\MessageCollection;
use App\Http\Resources\v1\User\Messenger\MessageResource;
use App\Http\Resources\v1\User\Messenger\ParticipationCollection;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Service;
use App\Models\v1\User;
use App\Models\v1\VisionBoard;
use App\Traits\Permissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lexx\ChatMessenger\Models\Message;
use Lexx\ChatMessenger\Models\Participant;
use Lexx\ChatMessenger\Models\Thread;

class Messenger extends Controller
{
    use Permissions;

    /**
     * Create a conversation with a admin.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chatAdmin(Request $request, $id = null)
    {
        $isNewThread = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required_unless:type,vision_board|string',
                'board_id' => 'required_if:type,vision_board|numeric',
            ]);

            if ($request->type === 'vision_board') {
                $board = VisionBoard::find($request->input('board_id'));
                $this->authorize('be-owner', [$board, 'You are not authorized to share this vision board']);
            }
        }

        $super = User::isOnlineWithPrivilege('super', false, [Auth::id()])->get('id')->pluck('id')->toArray();

        // Find random online admin with support privileges that is not the current user and is not a super admin.
        $admin = User::isOnlineWithPrivilege('support', true, array_merge($super, [Auth::id()]))->first();

        // If there are no online admins with support privileges then:
        if (! $admin) {
            $admin = User::isOnlineWithPrivilege('support', false, array_merge($super, [Auth::id()]))->first();
        }

        // If there are no admins with support privileges then assign the conversation to a super admin.
        if (! $admin && count($super) > 0) {
            $admin = User::find($super[0]);
        }

        if (! $admin && count($super) < 1) {
            return $this->buildResponse([
                'message' => 'There are currently no support assistants, please check back later.',
                'status' => 'error',
                'error_code' => '09',
                'status_code' => HttpStatus::NOT_FOUND,
            ]);
        }

        $participants = collect([$admin->id ?? $super[0], Auth::id()])->filter()->merge($super)->toArray();

        // Find the conversation between the current user and the admin.
        if (! $id) {
            $thread = Thread::between($participants)->where(function ($query) use ($request) {
                if ($request->route()->named('messenger.admin.support')) {
                    $query->where('type', 'support');
                } else {
                    $query->where('type', 'dispute');
                }
            })->withCasts(['data' => 'array'])->first();
        }

        // If the conversation does not exist then:
        if ($id && empty($thread)) {
            // Check if the request contains an ID, and try to load the associated conversation.
            $thread = Thread::withCasts(['data' => 'array'])->where(function ($query) {
                $query->where('type', 'support')->orWhere('type', 'dispute');
            })->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('slug', $id);
            })->firstOrFail();
        }

        // Otherwise create a new conversation
        if (empty($thread)) {
            $isNewThread = true;
            $thread = Thread::withCasts(['data' => 'array'])->create([
                'subject' => 'Admin Support: '.$admin->firstname,
                'slug' => base64url_encode(md5(time()).'admin-support-'.$admin->id.'-'.Auth::id()),
                'max_participants' => count($participants),
            ]);
            $thread->type = 'support';
            $thread->save();
        }

        if ($isNewThread && ! $thread->hasMaxParticipants()) {
            // Add the sender as a participant
            if (! $thread->hasParticipant(Auth::id())) {
                Participant::firstOrCreate([
                    'thread_id' => $thread->id,
                    'user_id' => Auth::id(),
                    'last_read' => new Carbon,
                ]);
            }

            foreach ($participants as $participant) {
                // Add the admin to the conversation
                if (! $thread->hasParticipant($participant)) {
                    $thread->addParticipant($participant);
                }
            }
            $thread->subject = 'Admin Support: '.$admin->firstname;
            $thread->save();
        }

        if ($request->isMethod('post')) {
            // If the conversation has been closed then:
            if ($thread->data && $thread->data['status'] === 'closed') {
                return (new ConversationResource($thread))
                    ->additional([
                        'slug' => $thread->slug,
                        'message' => __('You cannot send message to this conversation, as it has been closed'),
                        'status' => 'info',
                        'status_code' => HttpStatus::BAD_REQUEST,
                    ])
                    ->response()
                    ->setStatusCode(HttpStatus::BAD_REQUEST);
            }

            // Compose the message
            $message = Message::create([
                'thread_id' => $thread->id,
                'user_id' => Auth::id(),
                'body' => $request->input('message', ''),
            ]);

            // Attach a vision board to the message
            if ($request->type === 'vision_board') {
                $message->type = 'vision_board';
                $message->data = [
                    'type' => 'vision_board',
                    'board' => [
                        'id' => $board->id,
                        'title' => $board->title,
                        'slug' => $board->slug,
                        'image' => $board->image,
                    ],
                ];
                $message->save();
            }

            // check if pusher is allowed
            if (config('chatmessenger.use_pusher')) {
                $this->oooPushIt($message);
            }

            // $super = User::isOnlineWithPrivilege('super', false, [Auth::id()])->get('id')->pluck('id')->toArray();

            // // Find random admin with support privileges that is not the current user and is not a super admin.
            // $admin = User::isOnlineWithPrivilege('support', true, array_merge($super, [Auth::id()]))->first();

            // // If there are no admins with support privileges then:
            // if (! $admin) {
            //     $admin = User::isOnlineWithPrivilege('support', false, array_merge($super, [Auth::id()]))->first();
            // }

            // $participants = collect([$admin->id, Auth::id()])->merge($super)->toArray();
            // Prepare the response
            $additional = [
                'slug' => $thread->slug,
                'message' => $request->isMethod('post') ? 'Message sent successfully' : HttpStatus::message(HttpStatus::CREATED),
                'status' => 'success',
                'status_code' => $request->isMethod('post') ? HttpStatus::CREATED : HttpStatus::OK,
            ];

            // if ($isNewThread) {
            $additional['thread'] = new ConversationResource($thread);
            // }

            return (new MessageResource($message))
                ->additional($additional)
                ->response()
                ->setStatusCode($request->isMethod('post') ? HttpStatus::CREATED : HttpStatus::OK);
        }

        // List all admin chat messages
        $thread = $thread ?? Thread::between([Auth::id(), $admin->id])->where(function ($query) {
            $query->where('type', 'support')->orWhere('type', 'dispute');
        })->withCasts(['data' => 'array'])->firstOrFail();
        $messages = $thread->messages()->latest()->cursorPaginate();

        // Prepare the response
        $additional = [
            'slug' => $thread->slug,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ];

        // if ($isNewThread) {
        $additional['thread'] = new ConversationResource($thread);
        // }

        return (new MessageCollection($messages))
            ->additional($additional)
            ->response()
            ->setStatusCode(HttpStatus::OK);
    }

    /**
     * Get all of the conversations for a given user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        // $threads = $user->threads()->whereHas('messages')->latest('updated_ast')->get();
        $threads = Thread::withCasts(['data' => 'array'])->forUser($user->id)->latest('updated_at');

        if ($request->has('type')) {
            $threads->where('type', $request->input('type'));
        }

        $limit = $request->input('limit', 30);
        $conversations = $threads->cursorPaginate($limit);

        return (new ConversationCollection($conversations))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Get all of the messages for a given conversation or create a new conversation.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function messages(Request $request, $id = null, $mode = 'text')
    {
        $user = $request->user();
        $new = false;
        $ctype = 'private';
        $data = [];

        if ($mode === 'init') {
            $reciever = User::findOrFail($id);
            $queryThread = Thread::between([$user->id, $reciever->id])
                ->withCount('users')
                ->withCasts(['data' => 'array']);

            if ($request->has('type')) {
                $queryThread->where('type', $request->input('type', $ctype));
            }

            $queryThread->where('type', '!=', 'dispute');

            $thread = $queryThread->first();

            if (! $thread || $thread->hasMaxParticipants()) {
                $thread = null;
            }
        } elseif ($mode === 'service') {
            $service = Service::findOrFail($id);
            $reciever = $service->user;
            $queryThread = Thread::between([$user->id, $reciever->id])->withCasts(['data' => 'array']);
            $ctype = 'service';

            if ($request->has('type')) {
                $queryThread->where('type', $request->input('type', $ctype));
            } else {
                $queryThread->where('type', '!=', 'dispute');
            }
            $thread = $queryThread->first();
            $data = $this->buildService($service);
        } else {
            $queryThread = Thread::where('chat_threads.id', $id)->withCasts(['data' => 'array'])
                ->orWhere('slug', $id)->forUser($user->id);
            if ($request->has('type')) {
                $queryThread->where('type', $request->input('type', $ctype));
            }
            $thread = $queryThread->firstOrFail();
        }

        if (! $thread) {
            $thread = Thread::withCasts(['data' => 'array'])->create([
                'subject' => $reciever->fullname,
                'slug' => base64url_encode(md5(time()).'user-conversation-'.$user->id.time()),
                'max_participants' => 2,
            ]);

            $thread->type = $request->input('type', $ctype);
            $thread->save();

            if (! $thread->hasMaxParticipants()) {
                // Creator
                if (! $thread->hasParticipant($user->id)) {
                    Participant::create([
                        'thread_id' => $thread->id,
                        'user_id' => $user->id,
                        'last_read' => new Carbon,
                    ]);
                }

                if (! $thread->hasParticipant($reciever->id)) {
                    $thread->addParticipant($reciever->id);
                }

                $thread->type = $ctype;
                if (! empty($data)) {
                    $thread->data = $data;
                }
                $thread->save();
            }
            $new = true;
        }

        // List all chat messages
        $messages = $thread->messages()->latest()->withCasts(['data' => 'array'])->cursorPaginate();

        // Now return the response to the user`
        return (new MessageCollection($messages))->additional([
            'slug' => $thread->slug,
            'conversation' => new ConversationResource($thread),
            'message' => HttpStatus::message($new ? HttpStatus::CREATED : HttpStatus::OK),
            'status' => 'success',
            'status_code' => $new ? HttpStatus::CREATED : HttpStatus::OK,
        ])->response()->setStatusCode($new ? HttpStatus::CREATED : HttpStatus::OK);
    }

    /**
     * send a new message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $id = null, $mode = 'text')
    {
        $request->validate([
            'message' => 'required_unless:type,vision_board|nullable|string',
            'board_id' => 'required_if:type,vision_board|numeric',
        ]);

        if ($request->type === 'vision_board') {
            $board = VisionBoard::find($request->input('board_id'));
            $this->authorize('be-owner', [$board, 'You are not authorized to share this vision board']);
        }

        $user = $request->user();
        $ctype = 'private';
        $data = [];

        if ($mode === 'init') {
            $reciever = User::findOrFail($id);
            $queryThread = Thread::between([$user->id, $reciever->id])
                ->withCount('users')
                ->withCasts(['data' => 'array']);

            if ($request->has('type')) {
                $queryThread->where('type', $request->input('type', $ctype));
            }

            $thread = $queryThread->first();

            if (! $thread || $thread->hasMaxParticipants()) {
                $thread = null;
            }
        } elseif ($mode === 'service') {
            $service = Service::findOrFail($id);
            $reciever = $service->user;
            $thread = Thread::withCasts(['data' => 'array'])->between([$user->id, $reciever->id])->first();
            $ctype = 'service';
            $data = $this->buildService($service);
        } else {
            $thread = Thread::withCasts(['data' => 'array'])
                ->where('chat_threads.id', $id)->orWhere('slug', $id)
                ->forUser($user->id)->firstOrFail();
        }

        if (! $thread) {
            $thread = Thread::withCasts(['data' => 'array'])->create([
                'subject' => $reciever->fullname,
                'slug' => base64url_encode(md5(time()).'user-conversation-'.$user->id.time()),
                'max_participants' => 2,
            ]);

            $thread->type = $ctype;

            if (! empty($data)) {
                $thread->data = $data;
            }

            $thread->save();

            // Creator/Sender
            Participant::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'last_read' => new Carbon,
            ])->withCasts(['type' => 'array']);
        }

        if ($thread->data && $thread->data['status'] === 'closed') {
            // If the conversation has been closed then:
            if ($thread->data && $thread->data['status'] === 'closed') {
                return (new ConversationResource($thread))
                    ->additional([
                        'slug' => $thread->slug,
                        'message' => __('You cannot send message to this conversation, as it has been closed'),
                        'status' => 'info',
                        'status_code' => HttpStatus::BAD_REQUEST,
                    ])
                    ->response()
                    ->setStatusCode(HttpStatus::BAD_REQUEST);
            }
        }

        // Message
        $message = Message::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $request->input('message', ''),
        ]);

        if ($request->type === 'vision_board') {
            $message->type = 'vision_board';
            $message->data = [
                'type' => 'vision_board',
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'slug' => $board->slug,
                    'image' => $board->image,
                ],
            ];
            $message->save();
        }

        if (isset($reciever) && ! $thread->hasParticipant($reciever->id)) {
            $thread->addParticipant($reciever->id);
        }

        // check if pusher is allowed then send notification
        if (config('chatmessenger.use_pusher')) {
            $this->oooPushIt($message);
        }

        // Now return the response to the user`
        return (new MessageResource($message))->additional([
            'message' => 'Message sent successfully',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * List all the participants of a thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return array
     */
    public function participants(Request $request, $id)
    {
        $thread = Thread::withCasts(['data' => 'array'])
            ->where(config('chatmessenger.threads_table').'.id', $id)->orWhere('slug', $id)
            ->forUser($request->user()->id)->firstOrFail();

        $participants = $thread->participants()->with('user')->cursorPaginate($request->get('limit', 100));

        if ($request->hide_super and $participants && $this->setPermissionsUser($request->user())->checkPermissions('super') !== true) {
            $participants = $participants->filter(function ($participant) {
                return $this->setPermissionsUser($participant->user)->checkPermissions('super') === true;
            });
        }

        return (new ParticipationCollection($participants))->additional([
            'message' => 'Participants fetched successfully',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    public function buildService($service)
    {
        return [
            'service' => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'price' => $service->price,
                'user' => [
                    'id' => $service->user->id,
                    'name' => $service->user->fullname,
                    'avatar' => $service->user->avatar,
                    'username' => $service->user->username,
                    'lastname' => $service->user->lastname,
                    'firstname' => $service->user->firstname,
                ],
                'company' => $service->company ? [
                    'id' => $service->company->id,
                    'name' => $service->company->name,
                    'slug' => $service->company->slug,
                    'logo' => $service->company->logo,
                ] : [],
            ],
        ];
    }

    /**
     * Mark a thread as closed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return array
     */
    public function toggleState(Request $request, $id)
    {
        $this->validate($request, [
            'state' => 'required|in:open,closed',
        ]);

        $thread = Thread::withCasts(['data' => 'array'])
            ->where(config('chatmessenger.threads_table').'.id', $id)->orWhere('slug', $id)
            ->firstOrFail();

        // If the user is not a super admin or support then they can't close the thread but they can open it
        if ($request->state === 'closed' && $this->setPermissionsUser($request->user())->checkPermissions('super') !== true && $this->setPermissionsUser($request->user())->checkPermissions('support') !== true) {
            return (new MessageResource($thread))->additional([
                'message' => __('You are not allowed to close this thread'),
                'status' => 'error',
                'status_code' => HttpStatus::UNAUTHORIZED,
            ])->response()->setStatusCode(HttpStatus::UNAUTHORIZED);
        }

        // If the thread is already closed/open then don't close/open it again
        if ($request->state === ($thread->data['status'] ?? 'open')) {
            return (new MessageResource($thread))->additional([
                'message' => __('This thread is already '.$request->state),
                'status' => 'error',
                'status_code' => HttpStatus::UNAUTHORIZED,
            ])->response()->setStatusCode(HttpStatus::UNAUTHORIZED);
        }

        // Actually close/open the thread
        $thread->data = $thread->data
            ? [...$thread->data, 'status' => $request->state]
            : ['status' => $request->state];
        $thread->save();

        // Return the response
        return (new ConversationResource($thread))->additional([
            'message' => __('Thread :0 successfully.', [$request->state === 'closed' ? 'closed' : 'opened']),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Send the new message to Pusher in order to notify users.
     *
     * @param  Message  $message
     * @return void
     */
    public function oooPushIt(Message $message)
    {
        $thread = $message->thread;

        $data = [
            'id' => $message->id,
            'sent' => false,
            'message' => $message->body,
            'text' => $message->body,
            'subject' => $thread->subject,
            'data' => $message->data ?? [],
            'created_at' => $message->created_at->toISOString(),
            'avatar' => $message->user->avatar,
            'thread_id' => $message->thread_id,
            'slug' => $message->thread->slug,
            'conversation_id' => $message->thread->id,
            'conversation' => ['id' => $message->thread->id],
            'name' => $message->user->fullname,
            'sender' => collect((new UserResource($message->user))->toArray(request()))->except(['reg', 0, 1])->toArray(),
            'type' => $message->type ? $message->type : 'text',
            'stamp' => $message->created_at->toISOString(),
        ];

        broadcast(new MessageWasComposed($thread->slug, $data))->toOthers();
        // $recipients = collect($thread->participantsUserIds())->filter(fn ($id) => $id !== $sender->id)->toArray();
        // foreach ($recipients ?? [] as $recipient) {
        // event(new MessageWasComposed($message->thread->slug, $data));
        // }
    }
}