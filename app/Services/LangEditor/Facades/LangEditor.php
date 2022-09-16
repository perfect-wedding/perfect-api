<?php

namespace App\Services\LangEditor\Facades;

use App\Services\LangEditor\Tools\LangEditor as LangEditorContract;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;

/**
 * Class LangEditor
 *
 * @method static string[] allLanguages()
 * @method static array allTranslations()
 * @method static void setTranslation(string $key, string $lang, string $value)
 * @method static void deleteTranslations(string[] $keys)
 * @method static void routes(Router $router = null)
 */
class LangEditor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LangEditorContract::class;
    }
}
