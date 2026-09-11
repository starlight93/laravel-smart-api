<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class users extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'users';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["name","email","email_verified_at","password","remember_token","created_at","updated_at"];

    public $columns     = ["id","name","email","email_verified_at","password","remember_token","created_at","updated_at"];
    public $columnsFull = ["id:bigint","name:string:255","email:string:255","email_verified_at:datetime","password:string:255","remember_token:string:100","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [
    "email"=> "unique:users,email"
	];
    public $required    = ["name","email","password"];
    public $createable  = ["name","email","email_verified_at","password","remember_token","created_at","updated_at"];
    public $updateable  = ["name","email","email_verified_at","password","remember_token","created_at","updated_at"];
    public $searchable  = ["name","email","email_verified_at","password","remember_token","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
