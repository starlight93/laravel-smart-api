<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class failed_jobs extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'failed_jobs';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["uuid","connection","queue","payload","exception","failed_at"];

    public $columns     = ["id","uuid","connection","queue","payload","exception","failed_at"];
    public $columnsFull = ["id:bigint","uuid:string:255","connection:string:255","queue:string:255","payload:text","exception:text","failed_at:datetime"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [
    "uuid"=> "unique:failed_jobs,uuid"
	];
    public $required    = ["uuid","connection","queue","payload","exception","failed_at"];
    public $createable  = ["uuid","connection","queue","payload","exception","failed_at"];
    public $updateable  = ["uuid","connection","queue","payload","exception","failed_at"];
    public $searchable  = ["uuid","connection","queue","payload","exception","failed_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
