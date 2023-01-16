<?php

namespace App\Http\Resources\v1\Business;

use App\Http\Resources\v1\User\UserStripedResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $top_service = null;
        $route = $request->route()->getName();
        $show_top_service = in_array($route, ['categories.show']);

        if ($show_top_service) {
            $top_service = $request->route()
                ->parameter('category')
                ->services()
                ->withCount('reviews')
                ->orderBy('reviews_count', 'asc')
                ->where('company_id', $this->id)->first();
        }

        $privileged = false;
        if ($request->user()) {
            $privileged = $request->user()->id === $this->user_id || $request->user()->role === 'concierge';
        }

        $plp = $this->portfolios ? $this->portfolios->whereNotNull('images') : [];
        $p_cover = [
            'id' => $plp[0]->id ?? null,
            'title' => $plp[0]->title ?? null,
            'content' => $plp[0]->content ?? null,
            'image_url' => $plp[0]->images[0]->image_url ?? $this->banner_url,
        ];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'intro' => $this->intro,
            'location' => $this->location,
            'intro_elipses' => str($this->intro)->words(7),
            'address_elipses' => str($this->address)->words(7),
            'booked' => $this->when((bool) $this->task, true),
            'about' => $this->about,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'postal' => $this->postal,
            'address' => $this->address,
            'role' => $this->role,
            $this->mergeWhen($privileged, [
                'verified_data' => $this->verified_data,
                'rc_number' => $this->rc_number,
                'rc_company_type' => $this->rc_company_type,
            ]),
            'banner' => $this->banner_url,
            'logo' => $this->logo_url,
            'status' => $this->status,
            'stats' => $this->stats,
            'rating' => 5,
            'portfolio_cover' => $this->whenNotNull($p_cover),
            'top_service' => $this->whenNotNull($top_service),
            'created_at' => $this->created_at,
            'user' => $this->when(
                (! $request->user() || $request->user()->id !== $this->user_id || $request->user()->role === 'admin') &&
                ! in_array($route, ['services.service.show']), function () {
                    return new UserStripedResource($this->user);
                }),
            'task' => $this->when(
                (bool) $this->task && (bool) auth()->user() &&
                (
                    auth()->user()->id === $this->task->concierge_id ||
                    auth()->user()->id === $this->user_id ||
                    auth()->user()->role === 'admin'
                ),
                $this->task
            ),
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