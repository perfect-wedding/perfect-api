<?php

namespace App\Http\Resources\v1\Home;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $mini = stripos($request->route()->getName(), 'admin.content') !== false;

        $attached = $this->attached_model->map(function($models) {
            $collection = str(class_basename($models[0]))
            ->remove('homepage', false)->ucfirst()->append('Collection')->prepend('App\Http\Resources\v1\Home\\')->toString();

            if (class_exists($collection)) {
                $attach = (new $collection($models));
            }

            $key = str(class_basename($models[0]))->remove('homepage', false)->lower()->plural()->toString();
            return [$key => $attach];
        });

        return [
            "id" => $this->id,
            "homepage_id" =>  $this->homepage_id,
            "title" =>  $this->title,
            "subtitle" =>  $this->subtitle,
            "content" =>  $this->content,
            "slug" =>  $this->slug,
            "images" =>  $this->images,
            "parent" =>  $this->parent,
            "linked" =>  $this->linked,
            "iterable" =>  $this->iterable,
            'template' => $this->template,
            'page' => $this->page,
            "attached" => $mini
                ? $this->attached_model->map(function($m, $k) {
                        $classname = class_basename($m[0]);
                        return ['label' => str($classname)->remove('homepage', false)->toString(), 'value' => $classname];
                    })
                : (count($this->attached_model) ? $attached : null),
            "last_updated" =>  $this->updated_at
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