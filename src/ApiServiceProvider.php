<?php

namespace Starlight93\LaravelSmartApi;
use Illuminate\Support\ServiceProvider;
use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Carbon\Carbon;
use Starlight93\LaravelSmartApi\Helpers\EditorFunc as Ed;

class ApiServiceProvider extends ServiceProvider{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        $this->registerRoutes();
        $this->overrideConfigs();
        $this->registerCommands();
        $this->registerAliases();
        $this->registerValidators();
        $this->registerSchedulers();
        
        Request::macro('getMetaData', function() {
            return $this;
        });

        if( Api::isLumen() ){
            $this->app->singleton(
                ExceptionHandler::class,
                \Starlight93\LaravelSmartApi\Exceptions\LumenHandler::class
            );
        }else{
            $this->app->singleton(
                ExceptionHandler::class,
                \Starlight93\LaravelSmartApi\Exceptions\LaravelHandler::class
            );
        }
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        config(['start_time' => microtime(true)]);
        $this->mergeConfigFrom(__DIR__.'/../config/api.php', 'api');

        if( Api::isLumen() ){
            try{
                Facade::getFacadeRoot(); // if facades activated
            }catch(\Exception $err){
                $this->app->withFacades();
                $this->app->withEloquent();
            }

            $request = app('request');
            
            $this->app->configure('auth');
            $this->app->configure('services');
            $this->app->register(\Tymon\JWTAuth\Providers\LumenServiceProvider::class);
            $this->app->register(\App\Providers\AuthServiceProvider::class);
            $this->app->register(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);

            if(class_exists('\Illuminate\Mail\MailServiceProvider')){
                $this->mergeConfigFrom(__DIR__.'/../config/mail.php', 'mail');
                $this->app->register(\Illuminate\Mail\MailServiceProvider::class);

                $this->app->alias('mail.manager', Illuminate\Mail\MailManager::class);
                $this->app->alias('mail.manager', Illuminate\Contracts\Mail\Factory::class);

                $this->app->alias('mailer', Illuminate\Mail\Mailer::class);
                $this->app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
                $this->app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);
            }
            $this->app->middleware([
                \Starlight93\LaravelSmartApi\Http\Middleware\LumenCorsMiddleware::class
            ]);
            $this->app->routeMiddleware([
                'auth' => \App\Http\Middleware\Authenticate::class
            ]);
        }else{
            config(['sanctum'=>[]]);
            $this->app->register(\Tymon\JWTAuth\Providers\LaravelServiceProvider::class);
        }
        $this->app->register(\Starlight93\LaravelSmartApi\EditorServiceProvider::class);
        $this->app->register(\Laravel\Socialite\SocialiteServiceProvider::class);

        
        if( config('api.provider') && class_exists(config('api.provider')) ){
            $this->app->register( config('api.provider') );
        }
    }

    /**
     * Register the Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $commands = [
            Console\BackupCommand::class,
            \KitLoong\MigrationsGenerator\MigrateGenerateCommand::class,
            Console\ProjectModelCommand::class,
            Console\ProjectStartCommand::class,
            Console\ProjectDestroyCommand::class,
            Console\EnvKeyCommand::class,
        ];
        if( Api::isLumen() ){
            $commands[] = Console\StorageLinkCommand::class;
        }
        $this->commands( $commands );
    }
    /**
     * Register the routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {

        $namespace = 'Starlight93\LaravelSmartApi\Http\Controllers';
        // Restful API
        $this->app->router->group([
            'namespace' => $namespace,
        ], function () {
            require __DIR__.'/routesApi.php';
        });

        // Socialite
        $this->app->router->group([
            'namespace' => $namespace,
        ], function () {
            require __DIR__.'/routesSocialite.php';
        });

        // Restful API Public public_ function
        $this->app->router->group([
            'namespace' => $namespace,
            'prefix' => 'public'
        ], function () {
            require __DIR__.'/routesApiPublic.php';
        });
        
    }

    protected function registerAliases(){
        if (!class_exists('User')) {
            class_alias(\Starlight93\LaravelSmartApi\Models\User::class, 'User');
        }
        if(!class_exists('Api')){
            class_alias(\Starlight93\LaravelSmartApi\Helpers\ApiFunc::class, 'Api');
        }
        if(!class_exists('Log')){
            class_alias(\Illuminate\Support\Facades\Log::class, 'Log');
        }
        if(!class_exists('Carbon')){
            class_alias(\Carbon\Carbon::class, 'Carbon');
        }
        if(!class_exists('Socialite')){
            class_alias(\Laravel\Socialite\Facades\Socialite::class, 'Socialite');
        }

    }

    protected function overrideConfigs(){
        config(["auth.providers"=> [
            'users' => [
                'driver' => 'eloquent',
                'model' => \Starlight93\LaravelSmartApi\Models\User::class,
            ],
        ] ]);

        config(["auth.guards"=> [
            'api' => [
                'driver' => 'jwt',
                'provider' => 'users',
            ],
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
        ] ]);

        
        config(["logging.channels.stack"=> [
                'driver' => 'stack',
                'channels' => ['daily'],
                'ignore_exceptions' => false,
            ]
        ]);
        
        config(['services.google'=>[
            'client_id'     =>  env('GOOGLE_CLIENT_ID'), // public
            'client_secret' =>  env('GOOGLE_CLIENT_SECRET'),
            'redirect'      => url('/auth/callback/google')
        ]]);

        if(!Api::isLumen()){
            config(["auth.defaults"=> [
                'guard' => 'api',
                'passwords' => 'users',
            ] ]);
        }
    }

    protected function registerValidators(){
        Validator::extend('date_multi_format', function($attribute, $value, $formats) {
            foreach($formats as $format) {
              $parsed = date_parse_from_format($format, $value);
              if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0) {
                return true;
              }
            }
            return false;
        }, "format harus: [Y-m-d H:i:s], [Y-m-d] atau [d/m/Y].");

        Validator::extend('forbidden', function ($attribute, $value, $parameters) {
            return false;
        }, "dilarang dikirim ke server.");

        Validator::extend('no_space_only', function ($attribute, $value, $parameters) {
            if( str_replace( [' ',"\t","\n"], ["","",""], $value ) == '' ){
                return false;
            }
            return true;
        }, "tidak boleh hanya karakter kosong.");
    }

    protected function registerSchedulers(){
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            try{
                if( !Schema::hasTable('default_schedules') ) return;
                $tasks = DB::table('default_schedules')->where('status','ACTIVE')->get();
            }catch(\Exception $e){
                return trigger_error($e->getMessage());
            }
            foreach($tasks as $task){
                $daysArr = $task->days ? json_decode($task->days, true):[0, 1, 2, 3, 4, 5, 6];
                $every = trim( $task->every );
                $every_param = $task->every_param;
                if( !Str::contains($every, 'ly') && !Str::startsWith($every, 'every') && !in_array($every, ['cron']) ){
                    $every = "every".Str::camel($every);
                }

                $class = \Illuminate\Console\Scheduling\CallbackEvent::class;
                if( !method_exists($class, $every) ) return;
                if( !class_exists($task->class_name) ) return;
                
                $taskClass = new $task->class_name;
                $func = $task->func_name;
                if( !method_exists($taskClass, $func) ) return;
                
                $schedule->call(function ()use($task, $func, $taskClass) {
                    $paramArr = @$task->parameter_values ? json_decode($task->parameter_values,true):[];
                    call_user_func_array([$taskClass, $func], $paramArr );
                })
                ->before(function ()use($task) {
                    DB::table('default_schedules')->where('id', $task->id)->update([
                        'last_executed_at'=>Carbon::now()
                    ]);
                })
                ->after(function ()use($task) {
                    DB::table('default_schedules')->where('id', $task->id)->update([
                        'end_executed_at'=>Carbon::now()
                    ]);
                })->onSuccess(function () {
                    // The task succeeded...
                })->onFailure(function (Stringable $output)use($task) {
                    DB::table('default_schedules_failed')->insert([
                        'schedule_id' => $task->id,
                        'title' => $task->title,
                        'note' => $output->toString(),
                        'created_at'=>Carbon::now()
                    ]);
                })
                ->$every( $every_param )
                ->timezone('Asia/Jakarta')
                ->between( $task->start_at??'00:00', $task->end_at??'23:59' )
                ->days($daysArr);
            }
        });
    }
}