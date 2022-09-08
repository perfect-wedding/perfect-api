<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait Meta
{
    use Extendable;

    protected $model;

    protected $processables = [
        'contact',
    ];

    protected $allowed_metrics = [
        'saves',
        'downloads',
        'ratings',
        'views',
    ];

    public function saveStat(array $metrics = ['saves', 'downloads', 'ratings', 'views']): bool
    {
        if (in_array($this->processing, $this->processables)) {
            if (config('settings.rich_stats', false) === true) {
                $metric = $metrics[0] ?? 'saves';
                if (in_array($metric, $this->allowed_metrics)) {
                    $this->stats()->create([
                        'user_data' => $this->ipInfo(),
                        'user_id' => Auth::id(),
                        'metric' => $metric,
                    ]);
                }
            } else {
                $stat = $this->mini_stats()->firstOrCreate();
                collect($metrics)->each(function ($metric) use ($stat) {
                    if (in_array($metric, $this->allowed_metrics)) {
                        $stat->increment($metric);
                    }
                });
            }
        }

        return true;
    }

    public function generate_string($strength = 16, $group = 0, $input = null)
    {
        $groups = [
            '0123456789abcdefghi'.md5(time()).'jklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.time().rand(),
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.time().rand(),
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '01234567890123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];
        $input = $input ?? $groups[$group] ?? $groups[2];

        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    /**
     * @param    $collection
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options
     * @return LengthAwarePaginator
     */
    public function collectionPaginate($collection, int $perPage = 15, $currentPage = null, array $options = []): LengthAwarePaginator
    {
        $currentPage = $currentPage ?: (Paginator::resolveCurrentPage() ?: 1);
        $collection = $collection instanceof Collection ? $collection : Collection::make($collection);

        return new LengthAwarePaginator(
            $collection->forPage($currentPage, $perPage),
            $collection->count(),
            $perPage,
            $currentPage,
            $options
        );
    }
}