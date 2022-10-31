<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FeedbackResource;
use App\Models\v1\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'image' => ['sometimes', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'type' => ['required', 'in:bug,feedback,suggestion,complaint,other'],
            'message' => ['required', 'string', 'min:15'],
            'priority' => ['required', 'numeric', 'min:1', 'max:5'],
        ]);

        $feedback = new Feedback();

        $feedback->user_id = $request->user()->id;
        $feedback->priority = $request->priority;
        $feedback->type = $request->type;
        $feedback->path = $request->path;
        $feedback->message = $request->message;
        $feedback->save();

        return (new FeedbackResource($feedback))->additional([
            'message' => 'Thanks for your feedback, we might reach out to you if we find a need to do so.',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }
}
