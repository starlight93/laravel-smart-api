<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class sessions extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'sessions';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["user_id","ip_address","user_agent","payload","last_activity"];

    public $columns     = ["id","user_id","ip_address","user_agent","payload","last_activity"];
    public $columnsFull = ["id:string:255","user_id:bigint","ip_address:string:45","user_agent:text","payload:text","last_activity:integer"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["payload","last_activity"];
    public $createable  = ["user_id","ip_address","user_agent","payload","last_activity"];
    public $updateable  = ["user_id","ip_address","user_agent","payload","last_activity"];
    public $searchable  = ["user_id","ip_address","user_agent","payload","last_activity"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
