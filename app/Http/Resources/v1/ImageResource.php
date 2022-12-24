<?php

namespace App\Http\Resources\v1;

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
        return [
            'id' => $this->id,
            'file_id' => $this->id,
            'imageable_id' => $this->imageable_id,
            'description' => $this->description,
            'model' => $this->model,
            'src' => $this->file,
            'file' => $this->file,
            'meta' => $this->meta,
            'image_url' => $this->image_url,
            'src' => $this->file,
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
