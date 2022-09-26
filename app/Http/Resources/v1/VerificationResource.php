<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\User\UserResource;
use App\Services\AppInfo;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use ToneflixCode\LaravelFileable\Media;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class VerificationResource extends JsonResource
{
    use Fileable;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $adv = ! in_array($request->route()->getName(), ['concierge.companies.verify']);

        $disk = Storage::disk('protected');
        $fields = collect(json_decode($disk->get('company_verification_data.json'), JSON_FORCE_OBJECT));

        $docs = $this->when($this->docs->isNotEmpty(), $this->docs->mapWithKeys(function ($doc) {
            return [$doc->description => $doc->image_url];
        }), $fields->filter(fn ($f) => $f['type'] === 'file')->mapWithKeys(function ($f) {
            return [$f['name'] => $this->default_image];
        }));

        $custom_data = $this->data;

        $data = $fields->mapWithKeys(function ($data, $key) use ($custom_data, $docs) {
            if ($data['type'] === 'file') {
                $data['preview'] = $docs[$data['name']];
            }
            $value = $custom_data[$data['name']] ?? $docs[$data['name']] ?? '';
            $value = $data['type'] === 'checkbox' ? boolval($value) : $value;

            return [$data['name'] => $value];
        });

        return [
            'id' => $this->id,
            'user' => $this->when($adv, new UserResource($this->user)),
            'status' => $this->status,
            'exists' => $this->exists,
            'rejected_docs' => $this->rejected_docs,
            'reason' => $this->reason,
            'company' => $this->when($adv, new CompanyResource($this->company)),
            'concierge' => $this->when($adv, new UserResource($this->concierge)),
            'images' => $this->images,
            'data' => $data,
            'docs' => $docs,
            'observations' => $this->observations,
            'real_address' => $this->real_address,
            'apply_date' => $this->created_at,
            'verify_date' => $this->updated_at,
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