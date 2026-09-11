<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class cache_locks extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'cache_locks';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["key","owner","expiration"];

    public $columns     = ["key","owner","expiration"];
    public $columnsFull = ["key:string:255","owner:string:255","expiration:bigint"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["key","owner","expiration"];
    public $createable  = ["key","owner","expiration"];
    public $updateable  = ["key","owner","expiration"];
    public $searchable  = ["key","owner","expiration"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
