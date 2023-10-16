<?php

namespace Starlight93\LaravelSmartApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class ApiNativeController extends Controller
{
    public function index( Request $r, $name, $id = null ){
        if( Schema::hasTable($name) ){
            $builder = DB::table( $name );
            $updatedAt = Carbon::now()->format('Y-m-d H:i');
        }else{
            $q = DB::table("default_params")
                ->select("prepared_query", "params","updated_at")
                ->where("name", $name)->first();

            if(!$q) return response()->json([
                "message"=>"Maaf resource tidak tersedia"
            ], 404);

            $query = $q->prepared_query;
            $params = $q->params;
            $updatedAt = $q->updated_at;

            $builder = new \Staudenmeir\LaravelCte\Query\Builder(DB::connection());

            $builder->from($name)
                ->withExpression( $name, $query );

            $clientBindings = $r->all();
            $mustBindings = explode(',', $params);
            $fixBindings = [];

            foreach($mustBindings AS $bindKey){
                if(!$bindKey) continue;
                if( in_array($bindKey, array_keys($clientBindings)) ){
                    $fixBindings[$bindKey] = $clientBindings[$bindKey];
                }else{
                    $fixBindings[$bindKey] = NULL;
                }
            }
            // return $fixBindings;
            $builder->setBindings($fixBindings, 'expressions');
        }
        
        
        if( $id ) {
            return response()->json([
                "data" => $builder->whereRaw( "id=:id", ['id'=>$id] )->first()
            ]);
        }

        if( $r->has('orderby') ){
            $builder->orderBy($r->orderby,  $r->has('ordertype')? $r->ordertype:'ASC');
        }

        if( $r->has('search') && $r->search && $r->search!='null'){
            $searchText = strtolower( $r->search );
            $searchText = str_replace( [ '\\','(',')', "'" ],[ "\'", '\\\\','\(','\)' ], $searchText);
            $casterString = getDriver()=="pgsql"?"::text":"";
            $cols = Cache::get("schema_native_$name:$updatedAt")??[];
            $builder->where(function($q)use($cols, $casterString, $searchText){
                foreach($cols as $col){
                    if( !in_array($col, ['id']) ){
                        $q->orWhereRaw(DB::raw("LOWER($col$casterString) LIKE '%$searchText%'"));
                    }
                }
            });
        }

        if( $r->has('notin') && $r->notin && $r->notin!='null' ){
            $reqArr = explode(":", $r->notin);
            if( $idNotIn=$reqArr[1] ){
                $columnNotIn = $reqArr[0];
                $builder->where(function($q)use($columnNotIn, $idNotIn){
                    $col = str_replace("this.","", $columnNotIn);
                    $q->whereRaw( "$col not in ($idNotIn)" );
                });
            }
        }
        
        if( $r->has('where') ){
            $builder->whereRaw( $r->where );
        }

        if( $r->has('selectfield') ){
            $builder->selectRaw( $r->selectfield );
        }

        $data = $builder->simplePaginate($r->has('paginate') ? $r->paginate : 25);
        $cols = Cache::get("schema_native_$name:$updatedAt");
        if( !$cols ){
            $rows = (array)$data->toArray()['data'];
            if( count($rows)>0 ){
                $row = (array)$rows[0];
                Cache::put("schema_native_$name:$updatedAt", array_keys($row), 60*60*30);
            }
        }
        return $data;

        return $builder->paginate($r->has('paginate') ? $r->paginate : 25);
    }

    public function store( Request $r, $name, $id = null ){
        $array = $r->all();
        if(count($array) !== count($array, COUNT_RECURSIVE)){
            $now = Carbon::now();
            $id = [];
            foreach( $array as $dt ){
                $id[] = DB::table($name)->insertGetId( array_merge( $dt,[
                    'created_at' => $now
                ]));
            }
        }else{
            $id = DB::table($name)->insertGetId( array_merge( $r->all(),[
                'created_at' => Carbon::now()
            ]));
        }

        return response()->json([
            'message' => 'ok',
            'id' => $id
        ]);
    }

    public function update( Request $r, $name, $id ){
        DB::table($name)->where('id',$id)->update( array_merge( $r->all(),[
            'updated_at' => Carbon::now()
        ]));

        return response()->json([
            'message'=>'ok'
        ]);
    }

    public function destroy( Request $r, $name, $id ){
        DB::table($name)->where('id', $id)->delete();

        return response()->json([
            'message'=>'ok'
        ]);
    }
}