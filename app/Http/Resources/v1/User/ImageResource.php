<?php

namespace App\Http\Resources\v1\User;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $route = $request->route()->getName();

        return [
            'id' => $this->id,
            'description' => $this->description,
            'model' => $this->model,
            'meta' => $this->meta,
            'image_url' => $this->image_url,
            $this->mergeWhen(str($route)->contains(['vision.boards.show']), [
                'image_url' => $this->shared_image_url,
            ]),
            mb_strtolower($this->model ?? '') => [
                'id' => $this->imageable->id,
                'user_id' => $this->imageable->user_id,
                'title' => $this->imageable->title,
                'slug' => $this->imageable->slug,
                'disclaimer' => $this->imageable->disclaimer,
                'privacy' => $this->imageable->privacy,
                'info' => $this->imageable->info,
                'meta' => $this->imageable->meta,
                'user' => new UserResource($this->imageable->user),
                'created_at' => $this->imageable->created_at,
                'updated_at' => $this->imageable->updated_at,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}