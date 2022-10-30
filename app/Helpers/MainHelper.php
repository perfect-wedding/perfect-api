<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('rangeable')) {
    function rangeable($range, $del = '-', $prepend = '0')
    {
        $range = stripos($range, $del) === false
            ? ($prepend.$del.$range)
            : $range;

        return str($range)->explode($del)->map(function ($item) {
            return (int) trim($item);
        })->toArray();
    }
}

if (! function_exists('db_persist')) {
    /**
     * Persists the current working state of the database;
     *
     * @param  bool  $persist
     * @return \Illuminate\Support\Collection
     */
    function db_persist(bool $persist = false): Illuminate\Support\Collection
    {
        $disk = Storage::disk('protected');
        if (! $disk->exists('database.json') && $persist === true) {
            $disk->put('database.json', json_encode(config('database'), JSON_PRETTY_PRINT));
        }

        return collect(json_decode(Storage::disk('protected')->get('database.json'), JSON_FORCE_OBJECT));
    }
}

if (! function_exists('img')) {
    function img($image, $type = 'avatar', $cached_size = 'original', $no_default = false)
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        if ($image && Storage::exists((config('filesystems.default') === 'local' ? 'public/' : '').$image)) {
            $fpath = preg_match("/^(media\/|home\/){1,2}\w+/", $image) ? $image : $image;
            $photo = asset((config('filesystems.default') === 'local' ? $fpath : Storage::url($image)));
        // $photo    = asset( $image );
        } else {
            if ($no_default === true) {
                return null;
            }

            $photo = asset((config('filesystems.default') === 'local'
                ? env('default_'.$type, 'media/'.$type.(in_array($type, ['logo', 'avatar']) ? '.svg' : '.png'))
                : Storage::url(env('default_'.$type, 'media/'.$type.(in_array($type, ['logo', 'avatar']) ? '.svg' : '.png')))));

            $photo = config('settings.default_'.$type, $photo);
        }

        if (($cache = config('imagecache.route')) && ! Str::contains($photo, ['.svg'])) {
            $filename = basename($photo);

            return url("$cache/$cached_size/$filename");
        }

        $file_scheme = parse_url($photo, PHP_URL_SCHEME);
        $site_scheme = parse_url(config('app.url'), PHP_URL_SCHEME);

        return Str::of($photo)->replace($file_scheme.'://', $site_scheme.'://');
    }
}

if (! function_exists('money')) {
    function money($amount, $currency = null, $locale = null)
    {
        // $currency = $currency ?? config('settings.currency', 'USD');
        $currency_symbol = config('settings.currency_symbol', '₦');
        // $locale = $locale ?? config('app.locale', 'en');

        return $currency_symbol . number_format($amount, 2, '.', ',');
    }
}

if (! function_exists('pager')) {
    function pager($pager, $index = null, $key = null)
    {
        $show = [
            'page' => $pager->currentPage(),
            'total' => round($pager->total() / $pager->perPage()),
        ];

        $range = [
            'to' => $pager->hasMorePages()
                ? ($pager->currentPage() - 1) * $pager->perPage() + $pager->perPage()
                : $pager->total(),
            'from' => ($pager->currentPage() - 1) * $pager->perPage() + 1,
            'total' => $pager->total(),
        ];

        $set = [
            'show' => $show,
            'range' => $range,
        ];

        return isset($index, $key)
            ? $set[$index][$key]
            : (isset($index)
                ? $set[$index]
                : $set
            );
    }
}

if (! function_exists('random_img')) {
    function random_img($dir = null, $get_link = true)
    {
        try {
            $dir = trim($dir ?? 'images/bank', '/');
            $array = array_filter(File::files(public_path($dir)), function ($file) {
                if ($file->getExtension() === 'png' || $file->getExtension() === 'jpg' || $file->getExtension() === 'jpeg') {
                    return true;
                }
            });

            $file = collect($array)->random();

            if ($get_link === true) {
                return asset($dir.'/'.$file->getFileName());
            }

            return $file;
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException | \InvalidArgumentException $e) {
            $get_link === true ? '' : $e->getMessage();
        }
    }

    if (! function_exists('valid_json')) {
        /**
         * Matches a valid json string
         * Note that everything is atomic, JSON does not need backtracking if it is valid
         * and this prevents catastrophic backtracking
         *
         * @param    $str
         * @param    $get
         */
        function valid_json(string $str, $get = false, $default = null)
        {
            $data = json_decode($str);
            $isValid = (json_last_error() == JSON_ERROR_NONE);

            return $get === true && $isValid
                    ? $data
                    : ($default
                        ? $default
                        : $isValid
                    );
        }
    }
}