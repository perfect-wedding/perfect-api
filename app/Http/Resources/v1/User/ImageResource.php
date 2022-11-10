<?php

namespace App\Http\Resources\v1\User;

use App\Services\AppInfo;
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
            'file_id' => $this->id,
            'description' => $this->description,
            'model' => $this->model,
            'meta' => $this->meta ?? new \stdClass(),
            'image_url' => $this->image_url,
            'src' => $this->file,
            $this->mergeWhen(str($route)->contains(['vision.boards.show']), [
                'image_url' => $this->shared_image_url,
            ]),
            $this->mergeWhen(!str($request->path())->contains(str($this->model)->lower()), [
                mb_strtolower($this->model ?? '') => $this->imageable ? [
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
                ] : [],
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
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