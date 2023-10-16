<?php

namespace Starlight93\LaravelSmartApi\Console;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProjectDestroyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destroy all Files and Directories created by Editor API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ( !$this->confirm('This will clean and destroy all your projects, continue?') ) {
            return $this->info('Destroy Project Cancelled');
        }
        umask(0000);
        $dirs = [
            'app/Cores', 'app/Models/CustomModels','app/Models/GeneratedModels',
            'vendor/starlight93/laravel-smart-api/testlogs', 'resources/js/projects',
            'vendor/starlight93/laravel-smart-api/src/GeneratedModels',
            'resources/views/projects', 'database/migrations/projects',
            'database/migrations/alters', 'public/uploads'
        ];
        
        try{
            foreach( $dirs as $idx => $dir ){
                $dir = base_path( $dir );
                if( File::exists( $dir ) ) {
                    File::deleteDirectory( $dir );
                    $this->info("Deleted Successfully: $dir");
                }
            }

            $testPaths = \Api::isLumen()?"tests/*.php":"tests/Feature/*.php";
            $files = File::glob( base_path( $testPaths ) );
            foreach($files as $testFile){
                if(Str::endsWith($testFile, "/TestCase.php")||
                    Str::endsWith($testFile, "/CreatesApplication.php")||
                    Str::endsWith($testFile, "/ExampleTest.php"))
                {
                    continue;
                }
                if(File::exists( $testFile )) File::delete( $testFile );
                $this->info("Deleted Successfully testing files: $testFile");
            }

        }catch(\Exception $err){
            $this->error($dir. "-". $err->getMessage());
        }
    }
}
