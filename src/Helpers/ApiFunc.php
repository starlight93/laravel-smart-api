<?php

namespace Starlight93\LaravelSmartApi\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Jfcherng\Diff\DiffHelper;
use Illuminate\Support\Facades\Mail;
use Starlight93\LaravelSmartApi\Mails\SendMailable;
use Starlight93\LaravelSmartApi\Helpers\EditorFunc as Ed;
use Carbon\Carbon;

class ApiFunc {
    
    public static function req($key = null, $default = null) // api
    {
        $data =  config('request')?json_decode(json_encode( config('request') )):app()->request->all();
        if($key){
            $val = isset($data->$key)? $data->$key : $default;
            if( is_string($val) && in_array( Str::lower($val), ['false','true'])){
                $val=filter_var(Str::lower($val), FILTER_VALIDATE_BOOLEAN);
            }
            return $val;
        }
        return $data;
    }


    public static function req2( $key = null, $default = null ) // api
    {
        $pairs = explode("&", !app()->request->isMethod('GET') ? file_get_contents("php://input") : (@$_SERVER['QUERY_STRING']??@$_SERVER['REQUEST_URI']));
        $data = (object)[];
        foreach ($pairs as $pair) {
            $nv = explode("=", $pair);
            if(count($nv)<2) continue;
            $name = urldecode($nv[0]);
            $value = urldecode($nv[1]);
            $data->$name = $value;
        }
        
        if($key!==null){
            if( Str::contains($key, '%') ){
                $key = str_replace( '%', '', $key );
                $newData = [];
                foreach((array)$data as $keyArr=>$dt){
                    if( Str::startsWith( $keyArr, $key ) ){
                        $newData[$keyArr] = $dt;
                    }
                }
                $data = (object) $newData;
            }else{
                $val = isset($data->$key)? $data->$key : $default;
                if( is_string($val) && in_array( Str::lower($val), ['false','true'])){
                    $val=filter_var(Str::lower($val), FILTER_VALIDATE_BOOLEAN);
                }
                return $val;
            }
        }
        return $data;
    }


    
    public static function getTableOnly(string $tableName) : string // for api
    {
        if( Str::contains($tableName, ".") ){
            $exploded = explode(".", $tableName);
            return end($exploded);
        }
        return $tableName;
    }

    /**
     * Casts from self::request param for all basic models
     */
    public static function getCastsParam():array // for api
    {
        $casters = [];
        if(self::req('casts')){
            try{
                $rawCasters = explode(",", self::req('casts'));
            
                foreach($rawCasters as $key => $caster){
                    $casterArr = explode(":", $caster, 2);
                    $casters[$casterArr[0]] = $casterArr[1];
                }
            }catch(\Exception $e){
                abort(500,json_encode(["error"=>["casts parameter has wrong format"]]));
            }
        }
        return $casters;
    }

    public static function getData( object $model, object $params )
    {
        $table = $model->getTable();
        $className = class_basename( $model );
        
        $givenScopes = [];
        if($table == config( "parentTable") && self::req('scopes')){
            $scopes = explode(",", self::req('scopes'));
            foreach( $scopes as $scope ){
                if( !$model->hasNamedScope($scope) ){
                    abort(422,json_encode([
                        'message'=>"Scope $scope tidak ditemukan",
                        "resource"=>$className
                    ]));
                }
            }
            $givenScopes = $scopes;
        }

        $isParent = $className == (@app()->request->route('detailmodelname') || @app()->request->route('modelname'));
        $joinMax = isset($params->joinMax)?$params->joinMax:0;
        $pureModel=$model;    
        $modelCandidate = "\\".get_class($model);
        // $modelCandidate = "\App\Models\CustomModels\\$table";
        $modelExtender  = new $modelCandidate;
        $fieldSelected=[];
        // $metaColumns = [];
        foreach($model->getColumns() as $column){
            $fieldSelected[] = "$table.$column";
            // $metaColumns[$column] = "frontend";
        }
        $allColumns = $fieldSelected;
        $kembar = [];
        $joined = [];
        $enableJoin = self::req('join', true);
        if( $isParent ){
            $enableJoin = self::req('join', true);
        }else{
            $enableJoin = self::req2("$className.join", true);
        }
        $enableJoin = is_bool($enableJoin) ? $enableJoin : (strtolower($enableJoin) === 'false' ? false : true);
        if( $enableJoin ){
            $unjoins = !@$params->caller && self::req('unjoin')?array_map(Fn($d)=>$d."_id",explode( ',', self::req('unjoin') ) ):[];
            $selectFields = !@$params->caller && self::req('selectfield') ? array_map(Fn($d)=>explode(".",$d)[0],array_filter(explode( ',', self::req('selectfield') ),Fn($d)=>Str::contains($d,".") )):[];

            foreach( $model->joins as $join ){
                $arrayJoins=explode("=",$join);
                $arrayParents=explode(".",$arrayJoins[0]);

                if(count($arrayParents)>2){
                    $parent = $arrayParents[1];
                    $fullParent = $arrayParents[0].".".$arrayParents[1];
                }else{
                    $parent = $arrayParents[0];
                    $fullParent = $parent;
                }

                $parentClassString = "\App\Models\CustomModels\\$parent";
                if( !class_exists($parentClassString) ){
                    continue;
                }

                $joined[]=$parent;
                $onParent = $arrayJoins[0];
                $onMe = $arrayJoins[1];
                $meArr = explode( ".", $onMe );

                if( $unjoins && in_array( end( $meArr ), $unjoins ) ){
                    continue;
                }

                if(@$params->caller && @$params->caller==$parent && Str::replace('_id','',end( $meArr ))==@$params->caller ){
                    continue;
                }
                
                $aliasParent = str_replace('_id', env('SUFFIX_PARENT_TABLE',''), end( $meArr ));
                if( $aliasParent==='id' ){
                    $aliasParent = str_replace( $className."_", '', $parent);
                }

                if( $selectFields && !in_array( $aliasParent, $selectFields ) ){
                    continue;
                }

                if(getApiVersion()!=2){
                    if( !isset($kembar[$parent]) ){
                        $kembar[$parent] = 1;
                    }else{
                        $kembar[$parent] = $kembar[$parent]+1;
                    }
                }

                $parentName = $fullParent;
                if(getApiVersion()!=2 && $kembar[$parent]>1){
                    $parentName = "$fullParent AS ".$parent.(string)$kembar[$parent];
                    // $onParent = str_replace($parent,"tes".$parent.(string)$kembar[$parent],$onParent); //OLD CODE
                    $onParentArray=explode(".",$onParent);
                    if( count( $onParentArray )>2 ){
                        $onParent = $onParentArray[1].".".$onParentArray[2];
                    }
                    $onParent = str_replace($parent,$parent.(string)$kembar[$parent],$onParent);
                }

                if(getApiVersion()==2){
                    $parentName = "$fullParent AS $aliasParent";
                    $onParent = str_replace($fullParent,$aliasParent,$onParent);
                }

                $model = $model->leftJoin($parentName, $onParent, "=", $onMe);
                $parentClass = new $parentClassString;
                $parentClass->asParent = true;
                if( getApiVersion() !=2 && $kembar[$parent]>1 ){
                    $parentName = $parent.(string)$kembar[$parent];
                }
                foreach($parentClass->getColumns() as $column){
                    if( getApiVersion()==2 ){
                        $colTemp = Str::contains(strtolower($column), ' as ') ? $column : "$aliasParent.$column AS ".'"'.$aliasParent.".".$column.'"';
                    }else{
                        $colTemp = Str::contains(strtolower($column), ' as ') ? $column : "$parentName.$column AS ".'"'.$parentName.".".$column.'"';
                    }

                    $fieldSelected[]= $colTemp;
                    $allColumns[]   = "$parentName.$column";
                }
                
                if($joinMax>0){
                    if(getApiVersion()==2){
                        _joinRecursiveAlias($joinMax,$kembar,$fieldSelected,$allColumns,$joined,$model,$parent,$params);
                    }else{
                        _joinRecursive($joinMax,$kembar,$fieldSelected,$allColumns,$joined,$model,$parent,$params);
                    }
                }
            }
        }
        if( $isParent && $pureModel->isParamAllowed('selectfield') &&  (($isParent && self::req('selectfield')) || self::req2("$className.selectfield") ) ){
            $rawSelectFields = self::req2("$className.selectfield") ?? self::req('selectfield');
            $selectFields = explode(",", $rawSelectFields);
            $selectFields = array_map(function($d)use($table){
                return !Str::contains($d, '.')?"$table.$d":str_replace(["this.","\n","  ","\t"], ["$table.","","",""], $d);
            }, $selectFields);
            $fieldSelected = $selectFields;
        }
        
        if( $isParent && self::req('addselect') && $pureModel->isParamAllowed('addselect') ){
            $addSelect = str_replace("this.","$table.",strtolower( self::req('addselect')));
            $fieldSelected = array_merge( $fieldSelected, explode(",",$addSelect));
        }
        
        if( $pureModel->isParamAllowed('addjoin') && ( ($isParent && self::req('addjoin')) || self::req2("$className.addjoin")) ){
            $addJoin = self::req2("$className.addjoin") ?? self::req('addjoin');            
            $joiningString = str_replace("this.","$table.",strtolower($addJoin));
            $joins = explode( ",", $joiningString );
            foreach($joins as $join){
                $join = strtolower($join);
                if(strpos( $join, " and ")!==FALSE){
                    $join = explode(" and ",$join);
                    $joinedTable=explode(".",$join[0])[0];
                    $model = $model->leftJoin($joinedTable, function($q)use($join){
                        foreach($join as $statement){
                            $statement = str_replace(" ","",$statement);
                            $explodes = explode(".",$statement);
                            if( count($explodes)>2 ){
                                $parent = "{$explodes[0]}.{$explodes[1]}";
                            }else{
                                $parent = $explodes[0];
                            }
                            $onParent = explode("=",$statement)[0];
                            $onMe = explode("=",$statement)[1];
                            $q->on($onParent,"=",$onMe);
                        }
                    });
                }else{
                    $candParent = explode("=", $join)[0];
                    $explodes = explode(".", $candParent);
                    if( count($explodes)>2 ){
                        $parent = $explodes[0].".".$explodes[1];
                    }else{
                        $parent = $explodes[0];
                    }
                    $onParent = explode("=",$join)[0];
                    $onMe = explode("=",$join)[1];
                    $model = $model->leftJoin($parent,$onParent,"=",$onMe);
                }
            }
        }
        
        if(method_exists($modelExtender, "extendJoin")){
            $model = $modelExtender->extendJoin($model);
        }
        /**
         * Filter direct params misal this.column:21
         */
        $requestDataArr = (array) self::req();
        $directFilter = [];
        foreach($requestDataArr as $key => $val){
            if(Str::startsWith($key, "this_") || Str::startsWith($key, "this.")){
                $directFilter[]=$key;
                $model = $model->where(str_replace(["this_","this."],["$table.", "$table."],$key ), $val);
            }
        }

        if( $isParent && $pureModel->isParamAllowed('where') && self::req('where') ){
            $model = $model->whereRaw(str_replace("this.","$table.",urldecode( self::req('where') ) ) );
        }
        
        if( @$params->where_raw){
            $model = $model->whereRaw(str_replace("this.","$table.",urldecode( @$params->where_raw) ) );
        }

        if( self::isRoute('read_list_detail') ){
            $parentModelName = @app()->request->route('modelname');
            $parentModel = self::getCustom($parentModelName);
            $parentTable = self::getTableOnly($parentModel->getTable());
            $parentId = @app()->request->route('id');
            if($parentModel->useEncryption){
                $parentId = $parentModel->decrypt($parentId);
            }

            $model = $model->where(function($q)use( $parentTable, $parentId ){
                $q->where( $parentTable."_id", $parentId );
            });
        }

        if( self::isRoute('read_list_sub_detail') ){
            $parentModelName = @app()->request->route('detailmodelname');
            $parentModel = self::getCustom($parentModelName);
            $parentTable = self::getTableOnly($parentModel->getTable());
            $parentId = @app()->request->route('detailid');
            if($parentModel->useEncryption){
                $parentId = $parentModel->decrypt($parentId);
            }

            $model = $model->where(function($q)use( $parentTable, $parentId ){
                $q->where( $parentTable."_id", $parentId );
            });
        }

        if(  self::req("notin") && strpos( self::req("notin"),":")!==false ){
            $givenScopes[] = 'notin';
        }
        
        if( $isParent ){
            $givenScopes[] = 'filters';
            $givenScopes[] = 'directFilters';
            
            if( self::req('whereNull'))  $givenScopes[] = 'null';
            if( self::req('orWhereNull'))  $givenScopes[] = 'orNull';
            if( self::req('whereNotNull'))  $givenScopes[] = 'notNull';
            if( self::req('orWhereNotNull'))  $givenScopes[] = 'orNotNull';
        }
        
        if( self::req("query_name") && self::req('query_name')!=='null' && !app()->request->route('id')){
            $givenScopes[] = 'queryParam';
        }
        
        if(  self::req("orin") && strpos( self::req("orin"),":")!==false ){
            $givenScopes[] = 'orin';
        }

        if( self::req('search') && self::req('search')!=='null' ){
            $model=$model->search( $fieldSelected );
        }

        if( self::req('group_by' ) ){
            $model = $model->groupBy( DB::raw(str_replace("this.", "$table.", urldecode( self::req('group_by') )) ) );
        }
        
        if( $orderRaw = self::req('order_by_raw') ){
            $model = $model->orderByRaw( str_replace("this.","$table.",urldecode($orderRaw ) ) );
        }elseif( $orderCol = self::req('order_by', "$table.id")){
            $order =  str_replace("this.","$table.", $orderCol);
            if( !Str::contains($order, ".") ){
                $order = "$table.$order";
            }
            if( method_exists( $modelExtender, 'aliases') ){
                $aliases = $modelExtender->aliases();
                if(is_array($aliases)){
                    $key = array_search( $order,$aliases ) ;
                    if( $key ){
                        $order = $key;
                    }
                }
            }
        $model=$model->orderBy(DB::raw($order), self::req('order_type', 'DESC'));
        }
        
        $processedArr = [];
        foreach($fieldSelected as $idx => $field){
            if( !Str::contains(strtolower($field), ' as ') ){
                $tempArr = explode('.', $field);
                $colName = end($tempArr);
                if( in_array($colName, $processedArr) ){
                    $fieldSelected[ $idx ] = "$field AS ".'"'.$field.'"';
                }else{
                    $processedArr[] = end($tempArr);
                }
            }
        }

        $final  = $model->select(DB::raw(implode(",",$fieldSelected) ));
        
        $finalObj = (object)[
            'type'=>'get', 'caller'=>@$params->caller
        ];

        if(!@$params->caller){
        $data = $final->scopes($givenScopes)->final($finalObj);
        if( self::req('simplest')){
                $data = $data->simplePaginate( self::req2('paginate', ( @$params->paginate ?? 25) ),["*"], 'page', self::req('page', 1));
            }else{
                    $data = $data->paginate( self::req2('paginate', ( @$params->paginate ?? 25) ),["*"], 'page', self::req('page', 1));
            }
        }else{
        $data = $final->scopes($givenScopes)->final($finalObj)->get(); 
        }
        if( self::req("transform")==='false' ){
            if(!@$params->caller){
                $addData = collect(['processed_time' => self::getProcessedTime()]);
                $data = $addData->merge($data);
            }
            return $data;
        }
        if(@$params->caller){
            $tempData=$data->toArray();
            $fixedData=[];
            $index=0;
            foreach($tempData as $i => $row){
                $keys=array_keys($row);
                foreach($keys as $key){
                    if( count(explode(".", $key))>2 ){
                        $newKeyArray = explode(".", $key);
                        $newKey = $newKeyArray[1].".".$newKeyArray[2];
                        $tempData[$i][$newKey] = $tempData[$i][$key];
                        unset($tempData[$i][$key]);
                    }
                }
            }
            foreach($tempData as $row){
                $transformedData = self::reformatDataResponse($row);
                if(method_exists($modelExtender, "transformRowData")){
                    $transformedData = $modelExtender->transformRowData(self::reformatDataResponse($row));
                    if( gettype($transformedData)=='boolean' ){
                        continue;
                    }
                }

                $fixedData[$index] = $transformedData;
                foreach(["create","update","delete","read"] as $akses){
                    $func = $akses."roleCheck";
                    if( method_exists( $modelExtender, $func) ){
                        $fixedData[$index] = array_merge( ["meta_$akses"=>in_array( $akses, ['create','list'] ) ? $modelExtender->$func() 
                        : $modelExtender->$func( $row )], $fixedData[$index]);
                    }
                }

                if($pureModel->useEncryption){
                    $currentId = $pureModel->decrypt($fixedData[$index]['id']);
                }else{
                    $currentId = $fixedData[$index]['id'];
                }

                foreach($pureModel->details as $detail){
                    $detailArray = explode(".",$detail);
                    $detailClass = $detail;
                    if( count($detailArray)>1 ){
                        $detailClass = $detailArray[1];
                    }

                    $model      = self::getCustom($detailClass);
                    $columns    = $model->getColumns();
                    $fkName     = $pureModel->getTable();
                    if(!in_array($fkName."_id",$columns)){
                        $realJoins = $model->joins;
                        foreach($realJoins as $val){
                            $valArray = explode("=",$val);
                            if($valArray[0]==$fkName.".id"){
                                $fkName = $valArray[1];
                                break;
                            }
                        }
                    }else{
                        $fkName.="_id";
                    }
                    $p = (Object)[];
                    $p->where_raw   = $fkName."=".$currentId;
                    $p->joinMax     = 0;
                    $p->caller      = $pureModel->getTable();
                    $detailArray = explode('.', $detail);
                    $fixedData[$index][ count($detailArray)==1? $detail : $detailArray[1] ]  = $model->customGet($p);
                }
                $index++;
            }
            $func="transformArrayData";
            if( method_exists( $modelExtender, $func )  ){
                $newFixedData = $modelExtender->$func( $fixedData );
                $fixedData = gettype($newFixedData)=='array' ? $newFixedData : $fixedData;
            }
            $data   = $fixedData;
        }else{
            $tempData = $data->toArray()["data"];
            $fixedData=[];
            $index=0;        
            foreach($tempData as $i => $row){
                $keys=array_keys($row);
                foreach($keys as $key){
                    if( count(explode(".", $key))>2 ){
                        $newKeyArray = explode(".", $key);
                        $newKey = $newKeyArray[1].".".$newKeyArray[2];
                        $tempData[$i][$newKey] = $tempData[$i][$key];
                        unset($tempData[$i][$key]);
                    }
                }
            }
            foreach($tempData as $row){
                $transformedData = self::reformatDataResponse($row);
                if(method_exists($modelExtender, "transformRowData")){
                    $transformedData = $modelExtender->transformRowData(self::reformatDataResponse($row));
                    if( gettype($transformedData)=='boolean' ){
                        continue;
                    }
                }

                $fixedData[$index] = $transformedData;
                foreach(["create","update","delete","read"] as $akses){
                    $func = $akses."roleCheck";
                    if( method_exists( $modelExtender, $func) ){
                        $fixedData[$index] = array_merge( ["meta_$akses"=>in_array( $akses, ['create','list'] ) ? $modelExtender->$func() 
                        : $modelExtender->$func( $row )], $fixedData[$index]);
                    }
                }
                $index++;
            }
            $func="transformArrayData";
            if( method_exists( $modelExtender, $func )  ){
                $newFixedData = $modelExtender->$func( $fixedData );
                $fixedData = gettype($newFixedData)=='array' ? $newFixedData : $fixedData;
            }
            $data = array_merge([
                "data"=>$fixedData
            ],[
                "total"=> self::req('simplest')?null: $data->total(),
                "current_page"=>$data->currentPage(),
                "per_page"=>$data->perPage(),
                "from"=>$data->firstItem(),
                "to"=>$data->lastItem(),
                "last_page"=> self::req('simplest')?null:$data->lastPage(),
                "has_next"=>$data->hasMorePages(),
                "prev"=>$data->previousPageUrl(),
                "next"=>$data->nextPageUrl()
            ]);
            
            $data["processed_time"] = self::getProcessedTime();
        }
        if( env("RESPONSE_FINALIZER") ){
            $funcArr = explode(".", env("RESPONSE_FINALIZER"));
            $class = self::getCore($funcArr[0]) ?? self::getCustom($funcArr[0]);
            $func = $funcArr[1];
            $data = $class->$func( $data, $className );
        }
        return $data;
    }

    public static function getById($model, $params)
    {
        $table = $model->getTable();
        $className = class_basename( $model );
        $isParent = $className == (app()->request->route('detailmodelname') ?? app()->request->route('modelname'));
        $givenScopes = [];
        if($isParent && self::req('scopes')){
            $scopes = explode(",", self::req('scopes'));
            foreach( $scopes as $scope ){
                if( !method_exists($model, 'scope'.ucfirst($scope)) ){
                    abort(422,json_encode([
                        'errors'=>"Scope $scope tidak ditemukan",
                        "resource"=>$className
                    ]));
                }
            }
            $givenScopes = $scopes;
        }
        $joinMax = isset($params->joinMax)?$params->joinMax:0;
        $pureModel=$model;
        $modelCandidate = "\\".get_class($model);
        // $modelCandidate = "\App\Models\CustomModels\\$table";
        $modelExtender  = new $modelCandidate;
        $fieldSelected=[];
        $metaColumns=[];
        foreach($model->columns as $column){
            $fieldSelected[] = "$table.$column";
            $metaColumns[$column] = "frontend";
        }
        // if(!in_array(class_basename($model),array_keys(config('tables')))){
        //     $func = "metaFields";
        //     if( method_exists( $model, $func) ){
        //         $metaColumns = array_merge( $metaColumns, $model->$func($model->columns) );
        //     }
        //     config(['tables'=>array_merge(config('tables'), [class_basename($model)=>$metaColumns]) ]);
        // }
        $joined=[];
        $allColumns = $fieldSelected;
        if( @$params->join ){
            $kembar = [];
            foreach( $model->joins as $join ){
                $arrayJoins=explode("=",$join);
                $arrayParents=explode(".",$arrayJoins[0]);
    
                if(count($arrayParents)>2){
                    $parent = $arrayParents[1];
                    $fullParent = $arrayParents[0].".".$arrayParents[1];
                }else{
                    $parent = $arrayParents[0];
                    $fullParent = $parent;
                }
    
                $joined[]=$parent;
                $onParent = $arrayJoins[0];
                $onMe = $arrayJoins[1];
                $parentClassString = "\Starlight93\LaravelSmartApi\GeneratedModels\\$parent";
        
                $meArr = explode( ".", $onMe );
                $aliasParent = str_replace('_id', 's', end( $meArr ));
    
                if( !class_exists($parentClassString) ){
                    continue;
                }
                
                if( !isset($kembar[$parent]) ){
                    $kembar[$parent] = 1;
                }else{
                    $kembar[$parent] = $kembar[$parent]+1;
                }
                $parentName = $fullParent;
                if(self::req('api_version')!=2 && $kembar[$parent]>1){
                    $parentName = "$fullParent AS ".$parent.(string)$kembar[$parent];
                    $onParentArray=explode(".",$onParent);
                    if( count( $onParentArray )>2 ){
                        $onParent = $onParentArray[1].".".$onParentArray[2];
                    }
                    $onParent = str_replace($parent,$parent.(string)$kembar[$parent],$onParent);
                }
    
                if(self::req('api_version')==2){
                    $parentName = "$fullParent AS $aliasParent";
                    $onParent = str_replace($fullParent,$aliasParent,$onParent);
                    // trigger_error(json_encode([$parentName,$onParent,$onMe]));
                }
    
                $model = $model->leftJoin($parentName,$onParent,"=",$onMe);
                $parentClass = new $parentClassString;
                if(self::req('api_version') !=2 && $kembar[$parent]>1){
                    $parentName = $parent.(string)$kembar[$parent];
                }
                foreach($parentClass->columns as $column){
                    if( self::req('api_version')==2 ){
                        $colTemp = "$aliasParent.$column AS ".'"'.$aliasParent.".".$column.'"';
                    }else{
                        $colTemp = "$parentName.$column AS ".'"'.$parentName.".".$column.'"';
                    }
                    $fieldSelected[]= $colTemp;
                    $allColumns[]   = "$parentName.$column";
                }
            }
            if($joinMax>0){
                if(self::req('api_version')==2){
                    _joinRecursiveAlias($joinMax,$kembar,$fieldSelected,$allColumns,$joined,$model,$parent,$params);
                }else{
                    _joinRecursive($joinMax,$kembar,$fieldSelected,$allColumns,$joined,$model,$parent,$params);
                }
            }
        }
        if(@$params->selectfield || self::req('selectfield')){
            $rawSelectFields = self::req('selectfield') ?? @$params->selectfield;
            $selectFields = str_replace(["this.","\n","  ","\t"],["$table.","","",""], $rawSelectFields);
            $selectFields = explode(",", $selectFields);
            $fieldSelected= $selectFields;
        }
        
        if( isset($params->addSelect) && $params->addSelect!=null ){
            $addSelect = str_replace("this.","$table.",strtolower(@$params->addSelect));
            $fieldSelected = array_merge( $fieldSelected, explode(",",$addSelect));
        }
        
        if( @$params->addJoin || self::req('addjoin') ){
            $addJoin = self::req('addjoin') ?? @$params->addJoin;
            $joiningString = str_replace("this.","$table.",strtolower($addJoin));
            $joins = explode( ",", $joiningString );
            foreach($joins as $join){
                if(strpos( $join, " and ")!==FALSE){
                    $join = explode(" and ",$join);
                    $joinedTable=explode(".",$join[0])[0];
                    $model = $model->leftJoin($joinedTable, function($q)use($join){
                        foreach($join as $statement){
                            $statement = str_replace(" ","",$statement);
                            $explodes = explode(".",$statement);
                            if( count($explodes)>2 ){
                                $parent = "{$explodes[0]}.{$explodes[1]}";
                            }else{
                                $parent = $explodes[0];
                            }
                            $onParent = explode("=",$statement)[0];
                            $onMe = explode("=",$statement)[1];
                            $q->on($onParent,"=",$onMe);
                        }
                    });
                }else{
                    $candParent = explode("=",$join)[0];
                    $explodes = explode(".",$candParent);
                    if( count($explodes)>2 ){
                        $parent = $explodes[0].".".$explodes[1];
                    }else{
                        $parent = $explodes[0];
                    }
                    $onParent = explode("=",$join)[0];
                    $onMe = explode("=",$join)[1];
                    $model = $model->leftJoin($parent,$onParent,"=",$onMe);
                }
            }
        }
        
        if(method_exists($modelExtender, "extendJoin")){
            $model = $modelExtender->extendJoin($model);
        }
        if(app()->request->route('detailmodelname')){
            $parentModelName = app()->request->route('modelname');
            $parentId = app()->request->route('id');
    
            $model = $model->where(function($q)use( $parentModelName, $parentId ){
                $q->where( $parentModelName."_id", $parentId );
            });
        }
    
        $final = $model->select(DB::raw(implode(",",$fieldSelected) ));
        if(config('scopes')){
            foreach( config('scopes') as $scope ){
                if( method_exists($modelExtender, 'scope'.ucfirst($scope)) ){
                    $givenScopes[] = $scope;
                }
            }
        }
    
        $data = $final->scopes($givenScopes)->find(@$params->id);
        if( !$data ){
            abort(404,json_encode([
                "message"   => "Data tidak ditemukan",
                "resource" => $table,
                "id"        => @$params->id
            ]));
        }
        $data=$data->toArray();
        $keys=array_keys($data);
        foreach($keys as $key){
            if( count(explode(".", $key))>2 ){
                $newKeyArray = explode(".", $key);
                $newKey = $newKeyArray[1].".".$newKeyArray[2];
                $data[$newKey] = $data[$key];
                unset($data[$key]);
            }
        }
        $data = self::reformatDataResponse($data);
        if(method_exists($modelExtender, "transformRowData") && (!self::req("transform") || (self::req("transform") && self::req("transform")=='true'))){
            $data = $modelExtender->transformRowData($data);
        }
        if(@$params->single){
            return $data;
        }
        $id = @$params->id;
        foreach($pureModel->details as $detail){
            $detailArray = explode(".",$detail);
            $detailClass = $detail;
            if( count($detailArray)>1 ){
                $detailClass = $detailArray[1];
            }
            $modelCandidate = "\App\Models\CustomModels\\$detailClass";
            $model          = new $modelCandidate;
            $fk_child = array_filter($model->joins,function($join)use($pureModel){
                $parentString       = explode("=",$join)[0];
                $parentArray        = explode(".",$parentString);
                $parentNameString   = $parentArray[ 0 ] ;
                if( count( $parentArray )>2 ){
                    $parentNameString   = $parentArray[ 0 ].".".$parentArray[ 1 ] ;
                }
                if( $parentNameString == $pureModel->getTable() ){
                    return $parentNameString;
                }
            });
            $fk_child = explode( "=",array_values($fk_child) [ 0 ] )[1];
            $p = (Object)[];
            $p->where_raw   = "$fk_child=$id";
            $p->order_by    = null;
            $p->order_type  = null;
            $p->order_by_raw= null;
            $p->search      = null;
            $p->searchfield = null;
            $p->selectfield = null;
            $p->paginate    = null;
            $p->page        = null;
            $p->addSelect   = null;
            $p->addJoin     = null;
            $p->join        = true;
            $p->joinMax     = 0;
            $p->group_by    = null;
            $p->caller      = $pureModel->getTable();
            $detailArray = explode('.', $detail);
    
            $data[count($detailArray)==1? $detail : $detailArray[1] ]  = $model->customGet($p);
        }
        
        $keys   =   array_keys($data);
        foreach($keys as $key){
            if( count(explode(".", $key))>2 ){
                $newKeyArray = explode(".", $key);
                $newKey = $newKeyArray[1].".".$newKeyArray[2];
                $data[$newKey] = $data[$key];
                unset($data[$key]);
            }
        }
        $func="transformArrayData";
        if( method_exists( $modelExtender, $func )  && (!self::req("transform_array") || (self::req("transform_array") && self::req("transform_array")=='true')) ){
            $newFixedData = $modelExtender->$func( $data );
            $fixedData = gettype($newFixedData)=='array' ? $newFixedData : $data;
            $data   = $fixedData;
        }
        return $data;
    }

    public static function _joinRecursiveAlias($joinMax,&$kembar,&$fieldSelected,&$allColumns,&$joined,&$model,$tableName,$params){
        $tableStringClass = "\Starlight93\LaravelSmartApi\GeneratedModels\\$tableName";
        $currentModel = new $tableStringClass;
        
        foreach( $currentModel->joins as $join ){
            $arrayJoins=explode("=",$join);
            $arrayParents=explode(".",$arrayJoins[0]);

            if(count($arrayParents)>2){
                $parent = $arrayParents[1];
                $fullParent = $arrayParents[0].".".$arrayParents[1];
            }else{
                $parent = $arrayParents[0];
                $fullParent=$parent;
            }
            $onParent = $arrayJoins[0];
            $onMe = $arrayJoins[1];
            $joined[]=$fullParent;
            $parentClassString = "\App\Models\CustomModels\\$parent";

            $meArr = explode( ".", $onMe );
            $aliasParent = str_replace('_id', env('SUFFIX_PARENT_TABLE',''), end( $meArr ));

            if( !class_exists($parentClassString) ){
                continue;
            }
            if(isset($params->caller) && @$params->caller==$parent){
                continue;                
            }
                
            $parentName = "$fullParent AS $aliasParent";
            $onParent = str_replace($fullParent,$aliasParent,$onParent);
                
            $model = $model->leftJoin($parentName, $onParent, "=", $onMe);
            $parentClass = new $parentClassString;
            $parentClass->asParent = true;

            foreach($parentClass->getColumns() as $column){
                $colTemp        = Str::contains(strtolower($column), ' as ') ? $column : "$aliasParent.$column AS ".'"'.$aliasParent."_".$column.'"';
                $fieldSelected[]= $colTemp;
                $allColumns[]   = "$aliasParent.$column";
            }
            
            if($joinMax>1){
                _joinRecursiveAlias($joinMax,$kembar,$fieldSelected,$allColumns,$joined,$model,$parent,$params);
            }
        }
        
    }
    
    public static function _joinRecursive($joinMax,&$kembar,&$fieldSelected,&$allColumns,&$joined,&$model,$tableName,$params){
        $tableStringClass = "\Starlight93\LaravelSmartApi\GeneratedModels\\$tableName";
        $currentModel = new $tableStringClass;
        
        foreach( $currentModel->joins as $join ){
            $arrayJoins=explode("=",$join);
            $arrayParents=explode(".",$arrayJoins[0]);

            if(count($arrayParents)>2){
                $parent = $arrayParents[1];
                $fullParent = $arrayParents[0].".".$arrayParents[1];
            }else{
                $parent = $arrayParents[0];
                $fullParent=$parent;
            }
            // if(in_array($parent, $joined)){        
            //     continue;
            // }//PENTING
            $onParent = $arrayJoins[0];
            $onMe = $arrayJoins[1];
            $joined[]=$fullParent;
            $parentClassString = "\App\Models\CustomModels\\$parent";

            if( !class_exists($parentClassString) ){
                continue;
            }
            if(isset($params->caller) && @$params->caller==$parent){
                continue;                
            }
            if( !isset($kembar[$parent]) ){
                $kembar[$parent] = 1;
            }else{
                $kembar[$parent] = $kembar[$parent]+1;
            }
            
            $parentName = $fullParent;
            if($kembar[$parent]>1){
                $parentName = "$fullParent AS ".$parent.(string)$kembar[$parent];
                $onParentArray=explode(".",$onParent);
                if( count( $onParentArray )>2 ){
                    $onParent = $onParentArray[1].".".$onParentArray[2];
                }
                $onParent = str_replace($parent,$parent.(string)$kembar[$parent],$onParent);
            }
            $model = $model->leftJoin($parentName,$onParent,"=",$onMe);
            $parentClass = new $parentClassString;
            $parentClass->asParent = true;
            if($kembar[$parent]>1){
                $parentName = $parent.(string)$kembar[$parent];
            }
            foreach($parentClass->getColumns() as $column){
                $colTemp        = Str::contains(strtolower($column), ' as ') ? $column : "$parentName.$column AS ".'"'.$parentName.".".$column.'"';
                $fieldSelected[]= $colTemp;
                $allColumns[]   = "$parentName.$column";
            }
            if($joinMax>1){
                _joinRecursive($joinMax,$kembar,$fieldSelected,$allColumns,$joined,$model,$parent,$params);
            }
        }
        
    }

    public static function isRoute( string $val ): bool
    {
        return @app()->request->route('as')==$val;
    }

    
    public static function getBasic($name){
        if( Str::contains($name, '.') ){
            $nameArr = explode(".", $name);
            $name = end($nameArr);
        }
        $string = "\Starlight93\LaravelSmartApi\GeneratedModels\\$name";
        return class_exists( $string )?new $string:null;
    }

    public static function getCustom( $name )
    {
        if( Str::contains($name, '.') ){
            $nameArr = explode(".", $name);
            $name = end($nameArr);
        }
        if( config("custom_$name") ){
            return config("custom_$name");
        }
        
        $string = "\App\Models\CustomModels\\$name";
        $calledClass = class_exists( $string )?new $string:null;
        if($calledClass){
            config( ["custom_$name" => $calledClass] );
        }
        return $calledClass;
    }

    public static function reformatData( array $arrayData, $model=null ): array
    {
        $dataKey=["date","tgl","tanggal","_at","etd","eta"];
        $dateFormat = env("FORMAT_DATE_FRONTEND","d/m/Y");
        foreach($arrayData as $key=>$data){
            $datatype = self::getDataType($model,$key);
            if(is_array($data)){
                continue;
            }
            $isDate=in_array($datatype,['date','datetime','timestamp']);
            if($isDate){
                try{
                    $newData = Carbon::createFromFormat($dateFormat, $data)->format('Y-m-d');
                    $arrayData[$key] = $newData;   
                }catch(\Exception $e){
                    
                }
            }elseif( str_replace(["null","NULL"," "],["","",""],$data)==''){
                $arrayData[$key] = null;
            }
        }
        return $arrayData;
    }
    public static function reformatDataResponse( array $arrayData ): array
    {
        // deprecated, please override $this->casts in Model
        return $arrayData;
    }
    
    public static function getDriver(): string
    {
        return Schema::getConnection()->getDriverName();
    }

    public static function getDataType($model,$col){
        $columns = $model->columnsFull;
        foreach($columns as $column){
            $column = explode(":", $column);
            if($column[0]==$col){
                return $column[1];
            }
        }
        return null;
    }

    public static function getCore( $name ){
        $string = "\App\Cores\\$name";
        return class_exists( $string )?new $string:null;
    }

    public static function isLumen() :bool
    {
        return !(app() instanceof \Illuminate\Foundation\Application);
    }

    
    public static function isJson($args) {
        json_decode($args);
        return (json_last_error()===JSON_ERROR_NONE);
    }

    
    public static function getSchema( string $table, $header = null, $isRelation = false, $isDetailed = false ): array
    {
        $m = self::getBasic( $table );
        $body = [];
        foreach( $m->columnsFull as $col ){
            $explodedArr = explode( ":", $col );
            $key = $explodedArr[0];
            $body[ $key ] = str_replace( ":0", '', substr_replace( $col, "", 0, strlen("$key:") ) )
            .(in_array($key, $m->required)?":required":":optional")
            .(in_array($key, $m->unique)?":unique":"")
            .(in_array($key, $m->getGuarded())?":autocreate":"")
            .(in_array($key, ['created_at','updated_at','deleted_at'])?":autocreate":"")
            .($header && $key === $header."_id"?":autocreate":"");

            if( $isDetailed && !$isRelation && Str::endsWith($key, '_id') && method_exists( $m, str_replace('_id', '', $key) ) ){
                $parent = str_replace('_id', '', $key);
                if( $parent !== $header ){
                    $relatedTable = $m->$parent()->getRelated()->getTable();

                    if( Str::contains($relatedTable, '.') ){
                        $relatedTable = explode('.', $relatedTable)[1];
                    }
                    
                    $relatedKeys = self::getSchema( $relatedTable, null, true, $isDetailed );
                    foreach($relatedKeys as $relatedKey=>$relatedVal){
                        $body["$parent.$relatedKey"] = $relatedVal;
                    }
                }
            }
        }

        if($isRelation) return $body;
        
        foreach( $m->details as $detail ){
            $detailName = self::getTableOnly($detail);
            $body[ $detailName ] = [ self::getSchema( $detailName, $table ) ];
        }

        return $body;
    }

    public static function getRawData( string $query )
    {
        try{
            if(!Str::contains(Str::lower( $query ), 'limit 1')){
                $query = "$query limit 1";
            }
            $res = (array)DB::select( $query )[0];
            return array_values($res)[0];
        }catch(\Exception $e){
            return null;
        }
    }
    
    public static function SendEmail(string $to,string $subject, string $template)
    {
        if(!class_exists(\Illuminate\Mail\MailManager::class)){
            trigger_error('package illuminate/mail must be installed');
        }
        try{
            Mail::to($to)->send(new SendMailable($subject, $template ));         
        }catch(\Exception $e){
            return $e->getMessage();
        }
        return true;
    }
    
    public static function getProcessedTime()
    {
        return round(microtime(true)-config("start_time"),5);
    }

    
    public static function sanitizeString( $string, $force_lowercase = true, $anal = false ) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
                    "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                    "â€”", "â€“", ",", "<",  ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return $clean;
    }
}