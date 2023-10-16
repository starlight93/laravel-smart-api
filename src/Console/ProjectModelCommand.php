<?php

namespace Starlight93\LaravelSmartApi\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Starlight93\LaravelSmartApi\Http\Controllers\EditorController;
use Illuminate\Support\Facades\Cache;

class ProjectModelCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "project:model";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Re-Generate All Generated Models, *Empty* Custom Models & Cached Schema";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
            $cont = new EditorController;
            $req = new Request([ 'alter'=>'true', 'console'=>true ]);

            Cache::forget('migration-list');
            $cont->createModels($req, '******' );
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info("Models were generated sucessfully, dir: `app/Models/CustomModels`");
        $this->info("Schema was re-cached as key: `generated-models-schema`");
    }
}