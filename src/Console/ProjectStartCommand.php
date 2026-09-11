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
    protected $signature = 'project:start
        {--migrate : Migrate default migrations}
        {--auth= : Auth driver: jwt|sanctum|passport (skip pertanyaan interaktif)}';

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
        $migrate = $this->option('migrate');
        $driver  = $this->askAuthDriver();
        umask(0000);
        $dirs = [
            'app/Cores', 'app/Models/CustomModels',
            'app/tests', 'vendor/starlight93/laravel-smart-api/testlogs', 'resources/js/projects',
            'resources/views/projects', 'database/migrations/projects','vendor/starlight93/laravel-smart-api/src/GeneratedModels',
            'database/migrations/alters', 'storage','storage/app/public','storage/framework','storage/framework/cache','storage/framework/cache/data', 'public/uploads'
        ];
        
        try{
            foreach( $dirs as $dir ){
                $dir = base_path( $dir );
                if( File::exists( $dir ) ) {
                    chmod($dir, 0777);
                    $this->info("Chmod 777 to existing: $dir");
                }else{
                    mkdir($dir, 0777, true);
                    $this->info("Created Successfully: $dir");
                }
            }
            if( $migrate ){
                $migrationPath = "vendor/starlight93/laravel-smart-api/database/default_migrations";
                // migrate:refresh = rollback + migrate ulang -> DATA tabel default HILANG.
                if( !$this->confirm("--migrate menjalankan migrate:refresh pada $migrationPath: tabel default di-DROP dan dibuat ulang, datanya hilang. Lanjut?", false) ){
                    $this->warn("Migrasi dilewati.");
                }else{
                    $this->info("Migrating Default Tables... in $migrationPath");
                    Artisan::call("migrate:refresh",[
                        "--path" => $migrationPath , "--force"=>true
                    ]);
                    $this->info("Default tables are generated successfully");
                }
            }

            $this->info("Generating Models from Existing Database...");            
            Artisan::call("project:model");
            $this->info("Models are generated successfully"); 

            Artisan::call('project:env');   
            $this->info("Default Env Keys are generated successfully");

            if( $driver ){
                $this->setEnv('API_AUTH_DRIVER', $driver);
                $this->info("API_AUTH_DRIVER=$driver");
            }else{
                $driver = env('API_AUTH_DRIVER', 'jwt');
                $this->info("API_AUTH_DRIVER dibiarkan apa adanya ($driver)");
            }

            if( $driver === 'jwt' && !env('JWT_SECRET') ){
                Artisan::call('jwt:secret');
                $this->info("JWT Key has been updated in .env");
            }

            Artisan::call('storage:link');

        }catch(\Exception $err){
            $this->error($dir. "-". $err->getMessage());
        }
    }

    /**
     * Pilih driver auth: bawaan package (jwt) atau ikut aplikasi host (sanctum/passport).
     * Return null bila .env sudah punya API_AUTH_DRIVER dan --auth tidak diberikan
     * (project existing: jangan sentuh setelan yang sudah jalan).
     */
    protected function askAuthDriver() :?string
    {
        $driver = $this->option('auth');

        if( !$driver && env('API_AUTH_DRIVER') ) return null;

        if( !$driver ){
            $driver = $this->confirm('Pakai auth bawaan aplikasi (sanctum/passport)? Jawab no untuk JWT bawaan package.', false)
                ? $this->choice('Guard aplikasi yang dipakai?', ['sanctum', 'passport'], 0)
                : 'jwt';
        }

        $driver = strtolower($driver);
        if( !in_array($driver, ['jwt','sanctum','passport']) ){
            throw new \InvalidArgumentException("--auth harus jwt|sanctum|passport, diberi: $driver");
        }

        $required = [
            'sanctum'  => \Laravel\Sanctum\SanctumServiceProvider::class,
            'passport' => \Laravel\Passport\PassportServiceProvider::class,
        ];
        if( isset($required[$driver]) && !class_exists($required[$driver]) ){
            $this->warn("laravel/$driver belum terpasang. Jalankan: composer require laravel/$driver");
        }

        return $driver;
    }

    /** Set/replace satu key di .env. */
    protected function setEnv(string $key, string $value) :void
    {
        $path = $this->laravel->environmentFilePath();
        if( !file_exists($path) ) return;

        $content = file_get_contents($path);
        $line    = "$key=$value";
        $content = preg_match("/^$key=.*$/m", $content)
            ? preg_replace("/^$key=.*$/m", $line, $content)
            : rtrim($content).PHP_EOL.$line.PHP_EOL;

        file_put_contents($path, $content);
    }
}
