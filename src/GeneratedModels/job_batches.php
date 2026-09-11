<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class job_batches extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'job_batches';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["name","total_jobs","pending_jobs","failed_jobs","failed_job_ids","options","cancelled_at","created_at","finished_at"];

    public $columns     = ["id","name","total_jobs","pending_jobs","failed_jobs","failed_job_ids","options","cancelled_at","created_at","finished_at"];
    public $columnsFull = ["id:string:255","name:string:255","total_jobs:integer","pending_jobs:integer","failed_jobs:integer","failed_job_ids:text","options:text","cancelled_at:integer","created_at:integer","finished_at:integer"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["name","total_jobs","pending_jobs","failed_jobs","failed_job_ids"];
    public $createable  = ["name","total_jobs","pending_jobs","failed_jobs","failed_job_ids","options","cancelled_at","created_at","finished_at"];
    public $updateable  = ["name","total_jobs","pending_jobs","failed_jobs","failed_job_ids","options","cancelled_at","created_at","finished_at"];
    public $searchable  = ["name","total_jobs","pending_jobs","failed_jobs","failed_job_ids","options","cancelled_at","created_at","finished_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
