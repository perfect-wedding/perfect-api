<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Events\MessageWasComposed;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\Messenger\ConversationCollection;
use App\Http\Resources\v1\User\Messenger\ConversationResource;
use App\Http\Resources\v1\User\Messenger\MessageCollection;
use App\Http\Resources\v1\User\Messenger\MessageResource;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Service;
use App\Models\v1\User;
use App\Models\v1\VisionBoard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Musonza\Chat\Chat;
use Lexx\ChatMessenger\Models\Message;
use Lexx\ChatMessenger\Models\Participant;
use Lexx\ChatMessenger\Models\Thread;

class Messenger extends Controller
{
    /**
     * Create a conversation with a admin.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chatAdmin(Request $request, $id = null)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required|string',
                'board_id' => 'numeric|required_if:type,vision_board',
            ]);

            if ($request->type === 'vision_board') {
                $board = VisionBoard::find($request->input('board_id'));
                $this->authorize('be-owner', [$board, 'You are not authorized to share this vision board']);
            }
        }

        if ($id) {
            $thread = Thread::where(function ($query) {
                $query->where('type', 'support')->orWhere('type', 'dispute');
            })->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('slug', $id);
            })->firstOrFail();
        }

        $admin = User::whereRole('admin')->inRandomOrder()->first();
        if ($request->isMethod('post')) {
            $thread = $thread ?? Thread::between([Auth::id(), $admin->id])->where(function ($query) {
                $query->where('type', 'support')->orWhere('type', 'dispute');
            })->first();
            if (! $thread) {
                $thread = Thread::create([
                    'subject' => 'Admin Support: '.$admin->firstname,
                    'slug' => base64url_encode('admin-support-'.$admin->id.'-'.Auth::id()),
                ]);
                $thread->type = 'support';
                $thread->save();
            }
            // Message
            $message = Message::create([
                'thread_id' => $thread->id,
                'user_id' => Auth::id(),
                'body' => $request->input('message'),
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

            // Sender
            if (! $thread->hasParticipant(Auth::id())) {
                Participant::firstOrCreate([
                    'thread_id' => $thread->id,
                    'user_id' => Auth::id(),
                    'last_read' => new Carbon,
                ]);
            }

            // Recipients
            if (! $thread->hasParticipant($admin->id)) {
                $thread->addParticipant($admin->id);
                $thread->subject = 'Admin Support: '.$admin->firstname;
                $thread->save();
            }

            // check if pusher is allowed
            if (config('chatmessenger.use_pusher')) {
                $this->oooPushIt($message);
            }

            return (new MessageResource($message))->additional([
                'message' => $request->isMethod('post') ? 'Message sent successfully' : HttpStatus::message(HttpStatus::CREATED),
                'status' => 'success',
                'status_code' => $request->isMethod('post') ? HttpStatus::CREATED : HttpStatus::OK,
            ])->response()->setStatusCode($request->isMethod('post') ? HttpStatus::CREATED : HttpStatus::OK);
        }

        // List all admin chat messages
        $thread = $thread ?? Thread::between([Auth::id(), $admin->id])->where(function ($query) {
            $query->where('type', 'support')->orWhere('type', 'dispute');
        })->firstOrFail();
        $messages = $thread->messages()->latest()->cursorPaginate();

        return (new MessageCollection($messages))->additional([
            'slug' => $thread->slug,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
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
        $threads = Thread::forUserWithNewMessages(Auth::id())->latest('updated_at');

        if ($request->has('type')) {
            $threads->where('type', $request->input('type'));
        }

        return (new ConversationCollection($threads->get()))->additional([
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
            $thread = $queryThread->first();
            if (($thread->users_count ?? 0) > 2) {
                $thread = null;
            }
        } elseif ($mode === 'service') {
            $service = Service::findOrFail($id);
            $reciever = $service->user;
            $queryThread = Thread::between([$user->id, $reciever->id])->withCasts(['data' => 'array']);
            $ctype = 'service';

            if ($request->has('type')) {
                $queryThread->where('type', $request->input('type', $ctype));
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
                'slug' => base64url_encode('user-conversation-'.$user->id.time()),
            ]);

            $thread->type = $request->input('type', $ctype);
            $thread->save();

            // Creator
            Participant::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'last_read' => new Carbon,
            ]);

            $thread->addParticipant($reciever->id);
            $thread->type = $ctype;
            if (! empty($data)) {
                $thread->data = $data;
            }
            $thread->save();
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
            'message' => 'required|string',
            'board_id' => 'numeric|required_if:type,vision_board',
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
            $thread = Thread::between([$user->id, $reciever->id])
                ->withCount('users')
                ->withCasts(['data' => 'array'])
                ->first();
            if (($thread->users_count ?? 0) > 2) {
                $thread = null;
            }
        } elseif ($mode === 'service') {
            $service = Service::findOrFail($id);
            $reciever = $service->user;
            $thread = Thread::between([$user->id, $reciever->id])->withCasts(['data' => 'array'])->first();
            $ctype = 'service';
            $data = $this->buildService($service);
        } else {
            $thread = Thread::where('chat_threads.id', $id)->orWhere('slug', $id)->withCasts(['data' => 'array'])
                ->forUser($user->id)->firstOrFail();
        }

        if (! $thread) {
            $thread = Thread::withCasts(['data' => 'array'])->create([
                'subject' => $reciever->fullname,
                'slug' => base64url_encode('user-conversation-'.$user->id.time()),
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

        // Message
        $message = Message::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $request->message,
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
     * Send the new message to Pusher in order to notify users.
     *
     * @param  Message  $message
     * @return void
     */
    protected function oooPushIt(Message $message, $html = '')
    {
        $thread = $message->thread;
        $sender = $message->user;

        $data = [
            'id' => $message->id,
            'sent' => false,
            'message' => $message->body,
            'text' => $message->body,
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
