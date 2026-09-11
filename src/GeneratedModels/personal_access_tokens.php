<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class personal_access_tokens extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'personal_access_tokens';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["tokenable_type","tokenable_id","name","token","abilities","last_used_at","expires_at","created_at","updated_at"];

    public $columns     = ["id","tokenable_type","tokenable_id","name","token","abilities","last_used_at","expires_at","created_at","updated_at"];
    public $columnsFull = ["id:bigint","tokenable_type:string:191","tokenable_id:bigint","name:text","token:string:64","abilities:text","last_used_at:datetime","expires_at:datetime","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [
    "token"=> "unique:personal_access_tokens,token"
	];
    public $required    = ["tokenable_type","tokenable_id","name","token"];
    public $createable  = ["tokenable_type","tokenable_id","name","token","abilities","last_used_at","expires_at","created_at","updated_at"];
    public $updateable  = ["tokenable_type","tokenable_id","name","token","abilities","last_used_at","expires_at","created_at","updated_at"];
    public $searchable  = ["tokenable_type","tokenable_id","name","token","abilities","last_used_at","expires_at","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
