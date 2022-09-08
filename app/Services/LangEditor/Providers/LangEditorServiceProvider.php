<?php


namespace App\Services\LangEditor\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LangEditor\Contracts\LangEditor as LangEditorContract;
use App\Services\LangEditor\Tools\LangEditor;

class LangEditorServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->app->singleton(LangEditorContract::class, function(){
            return new LangEditor(
                app('path.lang'),
                app('translation.loader')
            );
        });

        $this->app->alias(
            LangEditorContract::class,
            class_basename(LangEditorContract::class)
        );

        $this->loadViewsFrom(
            realpath(__DIR__.'/../views'),
            'lang-editor'
        );
    }
}
