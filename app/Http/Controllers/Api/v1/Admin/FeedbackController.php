<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FeedbackCollection;
use App\Http\Resources\v1\FeedbackResource;
use App\Jobs\ProcessFeedback;
use App\Models\v1\Feedback;
use GrahamCampbell\GitHub\GitHubManager;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', ['feedback.manage']);
        // $this->authorize('usable', 'content');
        $query = Feedback::query();

        // Search and filter columns
        if ($request->search) {
            $query->whereFullText('message', $request->search);
        }

        // Get by type
        if ($request->type && $request->type != 'all') {
            $query->where('type', $request->type);
        }

        // Get by thread
        $query->thread($request->thread ?? false);

        // Reorder Columns
        if (! $request->thread) {
            foreach ($request->get('order', []) as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        $feedbacks = $query->paginate($request->get('limit', 15))->withQueryString();

        return (new FeedbackCollection($feedbacks))->additional([
            'message' => $feedbacks->isEmpty() ? __('There are no feedbacks for now.') : HttpStatus::message(HttpStatus::OK),
            'status' => $feedbacks->isEmpty() ? 'info' : 'success',
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
        $this->authorize('can-do', ['feedback.manage']);
        $this->validate($request, [
            'thread_id' => ['required', 'exists:feedback,id'],
            'image' => ['sometimes', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'message' => ['required', 'string', 'min:15'],
        ]);

        $thread = Feedback::findOrfail($request->thread_id);
        $feedback = new Feedback();

        $feedback->user_id = $request->user()->id;
        $feedback->message = $request->message;
        $feedback->thread_id = $request->thread_id;
        $feedback->priority = $thread->priority;
        $feedback->type = 'thread';
        $feedback->path = $thread->path;
        $feedback->save();

        return (new FeedbackResource($thread))->additional([
            'reply' => new FeedbackResource($feedback),
            'message' => __('Reply to feedback #:0 sent.', [$thread->id]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        $this->authorize('can-do', ['feedback.manage']);
        $this->validate($request, [
            'id' => ['required', 'exists:feedback,id'],
            'status' => ['required', 'string', 'in:pending,seen,reviewing,reviewed,resolved'],
        ]);

        $feedback = Feedback::findOrfail($request->id);

        $feedback->path = $request->status;
        $feedback->save();

        return (new FeedbackResource($feedback))->additional([
            'message' => __('Feedback status changed successfully.', [$feedback->id]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function github(Request $request, GitHubManager $github)
    {
        $this->authorize('can-do', ['feedback.manage']);
        $this->validate($request, [
            'id' => ['required', 'exists:feedback,id'],
            'type' => ['required', 'string', 'in:issue,pull_request'],
            'action' => ['required', 'string', 'in:open,close'],
        ]);

        $feedback = Feedback::findOrfail($request->id);
        ProcessFeedback::dispatch($feedback, $request->type, $request->action);

        return (new FeedbackResource($feedback))->additional([
            'message' => __("Github :0 for Feedback #:1 has been :2 (Please don't resend this request, refresh the page after a few seconds to check for updated status).", [ucfirst($request->type), $feedback->id, $request->action]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Feedback $feedback)
    {
        $this->authorize('can-do', ['feedback.manage']);

        return (new FeedbackResource($feedback))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::CREATED);
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
        $this->authorize('can-do', ['feedback.manage']);
        if ($request->items) {
            $items = collect($request->items)->map(function ($item) use ($request) {
                $item = Feedback::whereId($item)->first();
                if ($item) {
                    Feedback::thread($item->id)->delete();
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->id : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $items->count() === 1
                    ? __('Feedback #:0 has been deleted', [$items->first()])
                    : __(':0 feedbacks have been deleted.', [$items->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Feedback::findOrFail($id);
            Feedback::thread($item->id)->delete();
            $item->delete();

            return $this->buildResponse([
                'message' => __('Feedback #:0 has been deleted.', [$item->id]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
