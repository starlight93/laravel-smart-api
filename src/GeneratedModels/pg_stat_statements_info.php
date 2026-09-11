<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class pg_stat_statements_info extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'pg_stat_statements_info';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["dealloc","stats_reset"];

    public $columns     = ["dealloc","stats_reset"];
    public $columnsFull = ["dealloc:string","stats_reset:string"];
    public $rules       = "[]";
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = '[]';
    public $createable  = ["dealloc","stats_reset"];
    public $updateable  = ["dealloc","stats_reset"];
    public $searchable  = ["dealloc","stats_reset"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
