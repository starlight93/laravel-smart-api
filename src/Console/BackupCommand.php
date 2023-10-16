<?php

namespace Starlight93\LaravelSmartApi\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class BackupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "backup {--path=}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Backup Database to File";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->option('path')??(env('BACKUP_PATH')?env('BACKUP_PATH'):base_path('app_generated_backup'));

        try {
            $host = env('DB_HOST');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $database = env('DB_DATABASE');
            $port = env('DB_PORT');
            $file = date('Y-m-d H:i:s') . '-dump-' . $database . '.sql';

            // $file = str_replace(".sql", ".tar", $file);
            $command = sprintf( "pg_dump --no-owner -F t --dbname=\"postgresql://$username:$password@$host:$port/$database\" > %s", $sqlPath = "'$path/$file'");
            $this->info($command);
            exec($command);

            if( env("BACKUP_CALLBACK") ){
                // return $class->$func([
                //     'path' => $path,
                //     'withUpload' => $withUpload,
                //     'schema_path'=> $schemaSql,
                //     'sql_path'=> $sqlPath
                // ]);
            }
            
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info("database has been backed up to $path successfully");
    }
}