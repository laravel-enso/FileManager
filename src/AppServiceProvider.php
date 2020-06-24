<?php

namespace LaravelEnso\Files;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\Core\Models\User;
use LaravelEnso\DynamicMethods\Services\Methods;
use LaravelEnso\Files\DynamicsRelations\Uploads;
use LaravelEnso\Files\Models\Upload;
use LaravelEnso\Files\Services\FileBrowser;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        'file-browser' => FileBrowser::class,
    ];

    public function boot()
    {
        $this->load()
            ->mapMorphs()
            ->publish();
    }

    private function load()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->mergeConfigFrom(__DIR__.'/../config/files.php', 'enso.files');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        return $this;
    }

    private function mapMorphs()
    {
        Upload::morphMap();
        Methods::bind(User::class, [Uploads::class]);

        return $this;
    }

    private function publish()
    {
        $this->publishes([
            __DIR__.'/../config' => config_path('enso'),
        ], ['files-config', 'enso-config']);

        $this->publishes([
            __DIR__.'/../stubs/FileServiceProvider.stub' => app_path(
                'Providers/FileServiceProvider.php'
            ),
        ], 'file-provider');
    }
}
