<?php

namespace Starlight93\LaravelSmartApi\Console;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProjectStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default editable directories  (Models,Migrations,Tests,Cores) for Editting via editor API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        umask(0000);
        $dirs = [
            'app/Cores', 'app/Models/CustomModels',
            'app/tests', 'vendor/starlight93/laravel-smart-api/testlogs', 'resources/js/projects',
            'resources/views/projects', 'database/migrations/projects','vendor/starlight93/laravel-smart-api/src/GeneratedModels',
            'database/migrations/alters', 'storage','storage/app/public','storage/framework','storage/framework/cache','storage/framework/cache/data', 'public/uploads'
        ];
        
        try{
            foreach( $dirs as $idx => $dir ){
                $dir = base_path( $dir );
                if( File::exists( $dir ) ) {
                    chmod($dir, 0777);
                    $this->info("Chmod 777 to existing: $dir");
                }else{
                    mkdir($dir, 0777, true);
                    $this->info("Created Successfully: $dir");
                }
            }

            $migrationPath = "vendor/starlight93/laravel-smart-api/database/default_migrations";
            $this->info("Migrating Default Tables... in $migrationPath");            
            Artisan::call("migrate:refresh",[
                "--path" => $migrationPath , "--force"=>true
            ]);
            $this->info("Default tables are generated successfully"); 

            $this->info("Generating Models from Existing Database...");            
            Artisan::call("project:model");
            $this->info("Models are generated successfully"); 

            if(!env('JWT_SECRET')){
                $this->info("Generating JWT Secret");
                Artisan::call('jwt:secret');   
                $this->info("JWT Key has been updated in .env"); 
            }
            Artisan::call('project:env');   
            $this->info("Default Env Keys are generated successfully");

            Artisan::call('storage:link');


        }catch(\Exception $err){
            $this->error($dir. "-". $err->getMessage());
        }
    }
}
