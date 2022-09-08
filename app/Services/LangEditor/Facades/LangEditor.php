<?php


namespace App\Services\LangEditor\Facades;


use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use \App\Services\LangEditor\Tools\LangEditor as LangEditorContract;

/**
 * Class LangEditor
 * @package App\Services\LangEditor\Facades
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