<?php

namespace App\Http\Resources\v1;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StatResource extends ResourceCollection
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
            'downloads' => $this->when(isset($this['downloads']), function () {
                return $this['downloads'];
            }),
            'ratings' => $this->when(isset($this['ratings']), function () {
                return $this['ratings'];
            }),
            'saves' => $this->when(isset($this['saves']), function () {
                return $this['saves'];
            }),
            'views' => $this->when(isset($this['views']), function () {
                return $this['views'];
            }),
            'likes' => $this->when(isset($this['likes']), function () {
                return $this['likes'];
            }),
            'contacts' => $this->when(isset($this['contacts']), function () {
                return $this['contacts'];
            }),
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
