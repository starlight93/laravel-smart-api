<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class password_reset_tokens extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'password_reset_tokens';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["email","token","created_at"];

    public $columns     = ["email","token","created_at"];
    public $columnsFull = ["email:string:255","token:string:255","created_at:datetime"];
    public $rules       = [];
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["email","token"];
    public $createable  = ["email","token","created_at"];
    public $updateable  = ["email","token","created_at"];
    public $searchable  = ["email","token","created_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
