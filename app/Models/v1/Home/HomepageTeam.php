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
                $social_type = $social['type'] ?? null;
                $ext = $social_type === 'linkedin' ? '.com/in/' : '.com/';
                $class = $social_type === 'facebook' ? 'blue' : (
                    $social_type === 'twitter' ? 'blue-4' : (
                        $social_type === 'instagram' ? 'tf-text-pink' : (
                            $social_type === 'linkedin' ? 'blue-8' : 'primary'
                        )
                    )
                );

                return [
                    'type' => $social_type,
                    'class' => $class,
                    'link' => 'https://'.$social_type.$ext.($social['username'] ?? ''),
                    'username' => ($social['username'] ?? ''),
                ];
            }),
        );
    }

    /**
     * Scope this content to it's parent
     *
     * @param [type] $query
     * @param [type] $parent_id
     * @return void
     */
    public function scopeParent($query, $parent_id)
    {
        $query->where('parent', $parent_id);
    }
}