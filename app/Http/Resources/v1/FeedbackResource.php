<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\User\UserResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return collect([
            ...parent::toArray($request),
            $this->mergeWhen(str($request->route()->getName())->contains(['show', 'store']), [
                'replies' => new FeedbackCollection($this->replies()->limit($request->input('limit', 15))->get()),
                'issue_url' => $this->when(auth()->user()->role === 'admin', $this->issue_url),
            ]),
            'thread' => $this->replies->count(),
            'user' => new UserResource($this->user),
        ])->except([
            'issue_url', 'replies',
        ])->toArray();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return AppInfo::api();
    }
}