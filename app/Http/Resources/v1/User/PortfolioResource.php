<?php

namespace App\Http\Resources\v1\User;

use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $p_name = str($this->portfoliable_type)->afterLast('\\')->snake()->toString();
        $sub_path = $p_name == 'user'
            ? 'User\\'
            : ($p_name == 'company' ? 'Business\\' : '');

        $portfoliable_resource = 'App\Http\Resources\v1\\' . $sub_path . str($p_name)->studly()->toString() . 'Resource';
        $this->portfoliable = new $portfoliable_resource($this->portfoliable);

        // Check if the current route is not home.shared.portfolio
        // If it is, then we don't need to return the portfoliable
        // because it will be returned in the shared.portfolio
        if ($request->route()->getName() === 'home.shared.portfolio') {
            $this->portfoliable = new \stdClass();
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'content' => $this->content,
            'images' => new ImageCollection($this->images),
            'layout' => $this->layout,
            'active' => $this->active,
            'edge' => $this->edge,
            'meta' => $this->meta ?? new \stdClass(),
            $p_name => $this->portfoliable,
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