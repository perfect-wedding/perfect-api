<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class HomepageTeam extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'role',
        'info',
        'socials',
        'template',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'socials' => 'array',
    ];

    /**
     * The attributes that should be appended.
     *
     * @var array
     */
    protected $socials = [
        'socials',
    ];

    public function registerFileable()
    {
        $this->fileableLoader([
            'image' => 'default',
        ]);
    }

    public static function registerEvents()
    {
    }

    public function socials(): Attribute
    {
        return new Attribute(
            get: fn ($value) => collect(is_array($value) ? $value : json_decode($value))->map(function ($social, $values) {
                $social = collect($social)->toArray();

                return [
                    ...$social,
                    'username' => $social['username'] ?? pathinfo($social['link'], PATHINFO_BASENAME),
                ];
            }),
            set: fn ($value) => collect($value)->map(function ($social) {
                $ext = $social['type'] === 'linkedin' ? '.com/in/' : '.com/';
                $class = $social['type'] === 'facebook' ? 'blue' : (
                    $social['type'] === 'twitter' ? 'blue-4' : (
                        $social['type'] === 'instagram' ? 'tf-text-pink' : (
                            $social['type'] === 'linkedin' ? 'blue-8' : 'primary'
                        )
                    )
                );

                return [
                    'type' => $social['type'],
                    'class' => $class,
                    'link' => 'https://'.$social['type'].$ext.($social['username'] ?? ''),
                    'username' => ($social['username'] ?? ''),
                ];
            }),
        );
    }
}
