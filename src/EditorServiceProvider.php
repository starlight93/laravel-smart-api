<?php

namespace Starlight93\LaravelSmartApi;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;

class EditorServiceProvider extends ServiceProvider{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->overrideConfigs();
        $this->registerRoutes();
        $this->registerCommands();
        $this->registerAliases();
        $this->registerResources();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if( Api::isLumen() ){
            $this->app->withFacades();
            $this->app->withEloquent();
            $this->app->register(\KitLoong\MigrationsGenerator\MigrationsGeneratorServiceProvider::class);
        }
        $this->mergeConfigFrom(__DIR__.'/../config/editor.php', 'editor');
        if( class_exists(\MigrationsGenerator\MigrationsGeneratorServiceProvider::class) ){
            $this->app->register(\MigrationsGenerator\MigrationsGeneratorServiceProvider::class);
        }
    }

    /**
     * Register the Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            // Console\GenerateModelCommand::class,
            // Console\BackupCommand::class,
            \KitLoong\MigrationsGenerator\MigrateGenerateCommand::class
        ]);
    }
    /**
     * Register the routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $namespace = 'Starlight93\LaravelSmartApi\Http\Controllers';

        $this->app->router->group([
            'namespace' => $namespace,
            'prefix' => 'laradev',
            'middleware' => \Starlight93\LaravelSmartApi\Http\Middleware\EditorMiddleware::class
        ], function () {
            require __DIR__.'/routesEditor.php';
        });
        
        $this->app->router->group([
            'namespace' => $namespace,
            'prefix' => 'docs'
        ], function () {
            require __DIR__.'/routesDoc.php';
        });
    }

    protected function registerAliases(){
        if (!class_exists('Ed')) {
            class_alias('Starlight93\LaravelSmartApi\Helpers\EditorFunc', 'Ed');
        }
    }

    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'editor');
    }

    protected function overrideConfigs(){
        $defaultCorsPaths = config("colrs.paths");
        $defaultCorsPaths[] = "*/*";
        config(["cors.paths"=> $defaultCorsPaths ]);

        // override default migrator config
        config(["migrations-generator.migration_target_path" => base_path('database/migrations/projects') ]);
        config(["migrations-generator.filename_pattern" => [
            'table'       => '0_0_0_0_[name].php',
            'view'        => '0_0_0_0_[name].php',
            'procedure'   => '0_0_0_0_[name]_proc.php',
            'foreign_key' => '0_0_0_0reign_keys_to_[name]_table.php',
        ]]);
    }
}