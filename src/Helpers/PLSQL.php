<?php

namespace Starlight93\LaravelSmartApi\Helpers;
use Illuminate\Support\Facades\DB;

/**
 * Class untuk generate PLSQL: trigger, views, procedure, materialized views dengan PHP
 */
class PLSQL {

    protected $db = "mysql";
	private $when="";
	private $table;
	private $time="before" ;
	private $action;
	private $query;
	private $code;
	private $declare="";

    function __construct(){
        $this->db =  env('DB_CONNECTION');
    }

	public static function table($table){
		$class = new PLSQL();
		return $class->setTable($table);
	}

	public function setTable($table){
		$this->table = $table;
		return $this;
	}

	public function before($action){
		$this->action = $action;
		return $this;
	}

	public function after($action){
		$this->action = $action;
		$this->time = "after";
		return $this;
	}

	public function when($string){
		if($this->when != ""){
			$string= " AND ".$string;
		}
		$this->when .= $string;
		return $this;
	}

	public function whenOr($string){
		if($this->when != ""){
			$string= " OR ".$string;
		}
		$this->when .= $string;
		return $this;
	}

	public function script($text){
		
		$this->code = $text;
		return $this;
	}

	public function declare($variable){
		$text="";
		if( $this->db=='mysql'){
			if(is_array($variable)){
				foreach($variable  as $key=>$isi){
					$text .= "DECLARE $key $isi;";
				}
			}
			if($this->declare == ""){
				$text= $text;
			}
		}elseif( $this->db == 'pgsql'){
			if(is_array($variable)){
				foreach($variable  as $key=>$isi){
					if( strpos(strtolower($isi), "double") !== false ){
						$isi= $isi." precision";
					}
					$text .= "$key $isi;";
				}
			}
			if($this->declare == ""){
				$text= "DECLARE ".$text;
			}
		}

		$this->declare .= $text;
		return $this;
	}

	public function pgsqlCreate() {
        $tableSnake = str_replace( ".", "_", $this->table );
		if($this->when !== ""){
			$this->when = " when (".$this->when.") ";
		}
        $this->query="
		 DROP TRIGGER IF EXISTS ".$tableSnake."_".$this->time."_".$this->action." ON $this->table;
		 DROP FUNCTION IF EXISTS fungsi_".$tableSnake."_".$this->time."_".$this->action."();
		 CREATE OR REPLACE FUNCTION fungsi_".$tableSnake."_".$this->time."_".$this->action."() 
		 \n
		 RETURNS trigger
		 \n
		 LANGUAGE 'plpgsql'
		 AS
		 $$
		 $this->declare
		 BEGIN
		 $this->code
		 RETURN NEW;
		 END$$; 
		 
		 CREATE TRIGGER ".$tableSnake."_".$this->time."_".$this->action."
		 ".$this->time." ".$this->action." ON ".$this->table."
		 \n
		 FOR EACH ROW
		 \n
		 $this->when
		 \n
		 EXECUTE PROCEDURE fungsi_".$tableSnake."_".$this->time."_".$this->action."();
		 ";
        
        return $this->query;
	 }
	 
	 public function drop() {
        $tableSnake = str_replace( ".", "_", $this->table );
        $this->query="
        DROP TRIGGER IF EXISTS ".$tableSnake."_".$this->time."_".$this->action." ON $this->table;
        DROP FUNCTION IF EXISTS fungsi_".$tableSnake."_".$this->time."_".$this->action."();	
        ";
        try{
            DB::unprepared($this->query);
        }catch(\Exception $e){
            $this->query="
                DROP TRIGGER IF EXISTS ".$tableSnake."_".$this->time."_".$this->action.";
                DROP FUNCTION IF EXISTS fungsi_".$tableSnake."_".$this->time."_".$this->action."();		
            ";
            DB::unprepared($this->query);
            return "mysql trigger/func/view terhapus";
        }
    }

	 public function mysqlCreate() {
        $tableSnake = str_replace( ".", "_", $this->table );
		if($this->when !== ""){
			$this->when = " when (".$this->when.") ";
		}
		 $this->query="
		 DROP TRIGGER IF EXISTS ".$tableSnake."_".$this->time."_".$this->action." ;
		 CREATE TRIGGER ".$tableSnake."_".$this->time."_".$this->action."
		 ".$this->time."
		 ".$this->action." ON ".$this->table."
		 FOR EACH ROW
		 BEGIN 
			$this->declare
			$this->code 
		 END;
		 ";
		  return $this->query;
	 }

	 public function create(){
		if( $this->db == 'mysql' ){
			$query = $this->mysqlCreate();
		}elseif( $this->db == 'pgsql' ){
			$query = $this->pgsqlCreate();
		}
		DB::unprepared($query);
	 }

	public function createView(){
		DB::unprepared("
			CREATE OR REPLACE VIEW ".$this->table." AS ".$this->code.";");
	}

	public function createMaterializedView(){
		DB::unprepared("
			CREATE MATERIALIZED VIEW ".$this->table." AS ".$this->code.";");
	}

	public function index($column, $type="btree"){
		DB::unprepared("
			CREATE INDEX ".$this->table."_$column ON $this->table USING $type($column);");
	}
}