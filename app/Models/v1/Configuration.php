<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Media;

class Configuration extends Model
{
    use HasFactory;

    public function build($loadAll = false)
    {
        $config = $this->all();

        if ($loadAll) {
            return $config->map(function ($item) {
                $item->files;
                if ($item->type === 'file') {
                    $item->value = $item->files[0]->image_url ?? (new Media)->getDefaultMedia('default');
                }
                return $item;
            });
        }

        $config = $config->mapWithKeys(function ($item) {
            if ($item->type === 'files') {
                return [$item->key => $item->files];
            } elseif ($item->type === 'file') {
                return [$item->key => $item->files[0]->image_url ?? (new Media)->getDefaultMedia('default')];
            }

            return [$item->key => $item->value];
        });

        return $config;
    }

    public function files()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}