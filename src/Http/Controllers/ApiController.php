<?php

namespace Starlight93\LaravelSmartApi\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\support\Facades\Auth;
use Validator;
use Starlight93\LaravelSmartApi\Helpers\Cryptor;
use Starlight93\LaravelSmartApi\Helpers\Logger;
use App\Http\Controllers\Controller;
use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    private $requestData;
    private $requestMeta;
    private $operation  ='create';
    private $user;
    private $isAuthorized   = true;
    private $message       = "";
    private $messages       = [];
    private $errors       = [];
    private $success       = [];
    private $parentModelName;
    private $isDetailDirection = false;
    private $lastParentId;
    private $lastParentName;
    private $operationId=null;
    private $operationOK=true;
    private $customOperation=false;
    private $originalRequest;
    private $isMultipart = false;
    private $formatDate='Y-m-d';
    private $isBackdoor = false;
    private $isPatch = false;

    public function __construct(Request $request, $backdoor=false)
    {
        
        DB::disableQueryLog();
        $this->isBackdoor = $backdoor;
        $this->formatDate=env("FORMAT_DATE_FRONTEND","d/m/Y");
        $this->isMultipart = (strpos($request->header("Content-Type"),"multipart") !==FALSE)?true:false;
        $this->originalRequest = $request->capture();
        $this->requestData = $request->all();
        $this->requestMeta = $request->capture()->getMetaData();
        if(config('request')==null){
            config(['request'=>$this->requestData]);
            config(['requestHeader'=>$this->requestMeta->header()]);
            config(['requestMethod'=>$this->requestMeta->method()]);
            config(['requestOrigin'=>$this->requestMeta->path()]);
        }
        
        $this->parentModelName = @$request->route('modelname');
        $this->operationId = @$request->route('id');

        if($backdoor){
            return;
        }
        if( ! File::isDirectory(base_path('public/uploads') ) ) {
            File::makeDirectory(base_path('public/uploads') , 493, true);
        }
        if($this->isMultipart){
            $this->serializeMultipartData();
        }
        $this->user        = Auth::check()?Auth::user():null;        
        switch( strtolower($request->method()) ){
            case 'post' :
                $this->operation = "create";
                break;
            case 'delete' :
                $this->operation = "delete";
                break;
            case 'put' :
                $this->operation = "update";
                break;
            case 'patch' :
                $this->operation = "update";
                $this->isPatch = true;
                break;
            case 'get'  :
                $this->operation = $this->operationId?"read":"list";
                break;
        }
        if(!$this->is_model_exist( $this->parentModelName )){return;};
        if($this->operationId != null && !is_numeric($this->operationId) && !is_numeric((new Cryptor)->decrypt($this->operationId, true)) ){ $this->customOperation=true; return;}
        if(!$this->is_operation_authorized($this->parentModelName, $this->operationId )){return;};
        if(!$this->is_data_required($this->parentModelName, $this->requestData)){return;};
        if(!$this->is_data_valid($this->parentModelName, $this->requestData)){return;};
        if(!$this->is_data_not_unique($this->parentModelName, $this->requestData)){return;};
        if(!$this->is_model_deletable($this->parentModelName, $this->operationId)){return;};
        $this->is_detail_valid($this->parentModelName, $this->requestData);

        if( @$request->route('detailmodelname') ){
            abort(400, 'al');
            $this->isDetailDirection = true;
            $this->parentModelName = @$request->route('detailmodelname');
            $this->operationId = @$request->route('detailid');

            if(!$this->is_model_exist( $this->parentModelName )){return;};
            if($this->operationId != null && !is_numeric($this->operationId) && !is_numeric((new Cryptor)->decrypt($this->operationId, true))  ){
                $this->customOperation=true; 
                return;
            }
            if(!$this->is_operation_authorized($this->parentModelName, $this->operationId )){return;};
            if(!$this->is_data_required($this->parentModelName, $this->requestData)){return;};
            if(!$this->is_data_valid($this->parentModelName, $this->requestData)){return;};
            if(!$this->is_data_not_unique($this->parentModelName, $this->requestData)){return;};
            if(!$this->is_model_deletable($this->parentModelName, $this->operationId)){return;};
            $this->is_detail_valid($this->parentModelName, $this->requestData);
        }


        if( @$request->route('subdetailmodelname') ){
            $this->isDetailDirection = true;
            $this->parentModelName = @$request->route('subdetailmodelname');
            $this->operationId = @$request->route('subdetailid');

            if(!$this->is_model_exist( $this->parentModelName )){return;};
            if($this->operationId != null && !is_numeric($this->operationId) && !is_numeric((new Cryptor)->decrypt($this->operationId, true))  ){
                $this->customOperation=true; 
                return;
            }
            if(!$this->is_operation_authorized($this->parentModelName, $this->operationId )){return;};
            if(!$this->is_data_required($this->parentModelName, $this->requestData)){return;};
            if(!$this->is_data_valid($this->parentModelName, $this->requestData)){return;};
            if(!$this->is_data_not_unique($this->parentModelName, $this->requestData)){return;};
            if(!$this->is_model_deletable($this->parentModelName, $this->operationId)){return;};
            $this->is_detail_valid($this->parentModelName, $this->requestData);
        }
    }
    private function serializeMultipartData(){
        foreach( $this->requestData as $key=>$value ){
            if( is_numeric($value) || app()->request->hasFile($key) ){
                continue;
            }
            $triedJSON = json_decode( $value, true);
            $this->requestData [ $key ] = (json_last_error()==JSON_ERROR_NONE) ? $triedJSON:$value;
        }
    }
    private function getParentClass($model)
    {
        $string = "\\".get_parent_class($model);
        $newModel = new $string;
        return $newModel;
    }
    private function is_model_exist( $modelName )
    {
        $modelCandidate = "\Starlight93\LaravelSmartApi\GeneratedModels\\$modelName";
        if( !class_exists( $modelCandidate ) ){
            if( env('MODEL_RESOLVER') ){
                $resolvers = explode( ".", env('MODEL_RESOLVER') );
                $classResolver = getCore( $resolvers[0] ) ?? Api::getCustom( $resolvers[0] );
                $funcResolver = $resolvers[1];
                if( method_exists($classResolver,$funcResolver)){
                    $realModelName = $classResolver->$funcResolver( $modelName );
                    $modelCandidate = "\Starlight93\LaravelSmartApi\GeneratedModels\\$realModelName";
                    if( $realModelName && class_exists( $modelCandidate ) ){
                        config( ['action'.$modelName => Api::getCustom( $realModelName ) ] );
                        return true;
                    }else{
                        abort(404, json_encode([
                            'message'=>"Maaf Sumber Data tidak tersedia",
                            'resource'=>$realModelName
                        ]));
                    }
                }
            }
            abort(404, json_encode([
                'message'=>"Maaf Sumber Data tidak tersedia",
                'resource'=>$modelName
            ]));
        }
        return true;
    }
    private function is_operation_authorized($modelName, $id = null)
    {
        $model = Api::getCustom($modelName);

        if( method_exists($model, "getRoles" ) ){
            if( !$model->getRoles($this->originalRequest) ){
                $this->messages[] ="[UNAUTHORIZED]operasi $this->operation di [$modelName] dilarang!";
                $this->isAuthorized=false;
                return false;
            }
        }else{
            $function = $this->operation."RoleCheck";
            if(method_exists($model, $function)){
                $resultRole = in_array( $this->operation, ['create','list'] ) ? $model->$function() : $model->$function( $id );
                
                if( !$resultRole ){
                    abort(401, json_encode([
                        'message'=>config('reason')??"Maaf, anda tidak diperkenankan melakukan operasi ini",
                        "resource"=>$modelName
                    ]));
                }
            }

        }
        $model = null;
        return true;
    }
    private function is_data_required($modelName, $data, $operation=null)
    {
        return true;
        if($operation==null){
            $operation = $this->operation;
        }
        if( !in_array($operation,["create"]) ){return true;}
        $model = Api::getCustom($modelName); 
        $arrayRequired = $model->required;
        if(isset($data[0]) && is_array($data[0])){
            foreach ($data as $i => $isiData){
                $arrayFromRequest = array_keys($isiData);
                $notPresent = array_filter($arrayRequired, function($dt)use($arrayFromRequest){
                    if(!in_array($dt, $arrayFromRequest)){
                        return $dt;
                    }
                });
                if(count($notPresent)>0){
                    foreach($notPresent as $field){
                        $this->errors[] = "[REQUIRED]The ".str_replace("_id","",$field)." field is required, check your Detail.[$modelName] index [$i]";
                    }
                    $this->isAuthorized=false;
                    return false;
                }
            }
        }else{
            $arrayFromRequest = array_keys($data);
            $notPresent = array_filter($arrayRequired,function($dt)use($arrayFromRequest){
                if(!in_array($dt, $arrayFromRequest)){
                    return $dt;
                }
            });
            if(count($notPresent)>0){
                foreach($notPresent as $field){
                    $this->errors[] = "[REQUIRED]The $field field is required.[$modelName]";
                }
                $this->isAuthorized=false;
                return false;
            }
        }
        $model = null;
        return true;
    }
    private function is_data_valid($modelName, $data, $operation=null)
    {
        if($operation==null){
            $operation = $this->operation;
        }
        if( !in_array($operation,["create","update"]) ){
            return true;
        }

        $model          = Api::getCustom( $modelName );
        $operationValidator = $operation."Validator";
        $customString       = $operation."ValidatorResponse";
        $modelRules         = @$model->rules??[];
        $customResponseValidator = isset($model->$customString) ? $model->$customString : [];
        
        $arrayValidation    = $model->$operationValidator();

        if( $this->isPatch ){
            $arrayValidation = array_filter($arrayValidation, function($key){
                return req($key) ? true : false;
            }, ARRAY_FILTER_USE_KEY);
        }

        if(isset($model->autoValidator) && $model->autoValidator){
            $requiredFields    = $model->required;
            $datatypeValidator  = array_filter( $model->columnsFull, function($dt)use($arrayValidation){
                $keys = explode(":",$dt);
                $typeData = $keys[1];
                return !in_array($keys[0],array_keys($arrayValidation)) &&
                    ( strpos($typeData,"int")!==false
                    || in_array($typeData, ["decimal","float","numeric","double","boolean","date","timestamp","datetime","string","text","varchar"] ));
            });
            $autoValidators = [];
            foreach($datatypeValidator as $dt ){
                $validString = explode(":",$dt);
                $payload = strtolower($validString[0]);
                if( in_array($payload, ['created_at','updated_at']) ){
                    continue;
                }
                $typeData = strtolower($validString[1]);
                $length = count( $validString ) > 2 ? $validString[2] : null;
                $result = "";
                $fieldValidator = null;
                if( strpos($typeData,"int")!==false ){
                    $fieldValidator = (in_array($payload,$requiredFields)?"required":"nullable")."|integer";
                }elseif( in_array($typeData,["float","decimal","numeric","double"]  )){
                    $fieldValidator = (in_array($payload,$requiredFields)?"required":"nullable")."|numeric";
                }elseif( in_array($typeData,["boolean"] )){
                    $fieldValidator = (in_array($payload,$requiredFields)?"required":"nullable")."|boolean";
                }elseif( in_array($typeData,["date","datetime","timestamp"]  )){
                    $fieldValidator = (in_array($payload,$requiredFields)?"required":"nullable").'|date_multi_format:"Y-m-d H:i:s","Y-m-d G:i:s","Y-m-d H:i","Y-m-d G:i","Y-m-d","d/m/Y"' ;
                }elseif( in_array($typeData,["text","string"]  )){
                    $fieldValidator = (in_array($payload,$requiredFields)?"required":"nullable").( $this->isMultipart && in_array($payload,$model->fileColumns)?'' : (($length?"|max:$length":"")."|string") );
                }
                if($fieldValidator){
                    if( $operation!=='create' ){
                        $fieldValidator = str_replace("required","filled",$fieldValidator);
                    }
                    $autoValidators[$payload] = $fieldValidator;
                }
                if( @$modelRules[$payload] ){
                    $autoValidators[$payload] = (@$autoValidators[$payload]? ($autoValidators[$payload]."|".$modelRules[$payload]):$modelRules[$payload] );
                }
            }

            $keyAdditionalData = $operation."AdditionalData";
            $additionalData = $model->$keyAdditionalData;
            foreach( $additionalData as $key => $dt ){
                //  don't use this
                // $autoValidators[$key] = "forbidden";
            }
        }

        if(isset($data[0]) && is_array($data[0])){
            foreach ($data as $i => $isiData){
                if(isset($model->autoValidator) && $model->autoValidator){
                    if($model->useEncryption && isset( $isiData['id'] ) && !is_numeric( $isiData ['id'] )){
                        $isiData['id'] = $model->decrypt($isiData['id']);
                    }
                    $validator = Validator::make($isiData, $autoValidators, $customResponseValidator);
                    if ( $validator->fails() ) {
                        //  #invalid data
                        abort(422, json_encode([
                            'message'=>"Maaf data belum valid, silahkan dikoreksi",
                            "errors"=>$validator->errors(),
                            "resource"=>$modelName,
                            "line"=>$i+1
                        ]));
                    }
                }
                $isiData = Api::reformatData($isiData,$model);
                if( $operation=='update' && isset($isiData['id']) ){
                    $operationId = $isiData['id'];
                    if($model->useEncryption && !is_numeric( $operationId )){
                        $operationId = $model->decrypt($operationId);
                    }
                    $arrayValidation = array_map(function($dtm) use ($operationId){
                        if(strpos($dtm, "unique")!==false){
                            $dtm = $dtm.",$operationId";
                        }
                        return $dtm;
                    }, $arrayValidation);
                }
                $validator = Validator::make($isiData, $arrayValidation,  $customResponseValidator);
                if ( $validator->fails()) {
                    //  #invalid data
                    abort(422, json_encode([
                        'message'=>"Maaf data belum valid, silahkan dikoreksi",
                        "errors"=>$validator->errors(),
                        "resource"=>$modelName,
                        "line"=>$i+1
                    ]));
                }
            }
        }else{
            if(isset($model->autoValidator) && $model->autoValidator){
                if($model->useEncryption && isset( $data['id'] ) && !is_numeric( $data ['id'] )){
                    $data['id'] = $model->decrypt($data['id']);
                }
                $validator = Validator::make($data, $autoValidators, $customResponseValidator);
                if ( $validator->fails()) {
                    //  #invalid data
                    abort(422, json_encode([
                        'message'=>"Maaf data belum valid, silahkan dikoreksi",
                        "errors"=>$validator->errors(),
                        "resource"=>$modelName
                    ]));
                }
            }
            $data = Api::reformatData($data,$model);
            if( $operation=='update' ){
                $operationId = $this->operationId;
                if($model->useEncryption && !is_numeric( $operationId )){
                    $operationId = $model->decrypt($operationId);
                }
                $arrayValidation = array_map(function($dtm) use ($operationId){
                    if(!is_array($dtm) && strpos($dtm, "unique")!==false){
                        $dtm = $dtm.",$operationId";
                    }
                    return $dtm;
                }, $arrayValidation);
            }
            $validator = Validator::make($data, $arrayValidation, $customResponseValidator);
            if ( $validator->fails()) {
                //  #invalid data
                abort(422, json_encode([
                    'message'=>"Maaf data belum valid, silahkan dikoreksi",
                    "errors"=>$validator->errors(),
                    "resource"=>$modelName
                ]));
            }
        }
        return true;
    }
    private function is_data_not_unique($modelName, $data)
    {
        if( !in_array($this->operation,["create","update"]) ){return true;}
        $model          = Api::getCustom( $modelName );
        $arrayValidation    = $model->unique;
        if($this->operation == 'update'){
            $operationId = $this->operationId;
            if($model->useEncryption && !is_numeric( $operationId )){
                $operationId = $model->decrypt($operationId);
            }
            $newArrayValidation = [];
            foreach($arrayValidation as $key => $validation){
                $newArrayValidation[$key] = $validation.",$operationId";
            }
            $arrayValidation = $newArrayValidation;
        }
        if(isset($data[0]) && is_array($data[0])){
            foreach ($data as $i => $isiData){
                if($model->useEncryption && isset( $isiData['id'] ) && !is_numeric( $isiData ['id'] )){
                    $isiData['id'] = $model->decrypt($isiData['id']);
                }
                $validator = Validator::make($isiData, $arrayValidation);
                if ( $validator->fails()) {
                    //  #invalid data
                    abort(422, json_encode([
                        'message'=>"Maaf data belum valid, silahkan dikoreksi",
                        "errors"=>$validator->errors(),
                        "resource"=>$modelName
                    ]));
                }
            }
        }else{
            if($model->useEncryption && isset( $data['id'] ) && !is_numeric( $data ['id'] )){
                $data['id'] = $model->decrypt($data['id']);
            }
            $validator = Validator::make($data, $arrayValidation);
            if ( $validator->fails()) {
                //  #invalid data
                abort(422, json_encode([
                    'message'=>"Maaf data belum valid, silahkan dikoreksi",
                    "errors"=>$validator->errors(),
                    "resource"=>$modelName
                ]));
            }
        }
        return true;
    }
    private function checkDetailExist($key, $detailsArray){
        $keyArray = explode( ".", $key);
        if( count( $keyArray )>1 ){
            $key = $keyArray[1];
        }
        foreach( $detailsArray as $detail ){
            $detailStringArray = explode( ".", $detail);
            $detailString = $detail;
            if( count( $detailStringArray )>1 ){
                $detailString = $detailStringArray[1];
            }
            if($detailString==$key){
                return true;
                break;
            }
        }
        return false;
    }
    private function is_detail_valid($modelName, $data)
    {
        if( !in_array($this->operation,["create","update"]) ){return true;}
        // $modelCandidate = "\Starlight93\LaravelSmartApi\GeneratedModels\\$modelName";
        // $model          = new $modelCandidate;
        $model          = Api::getCustom( $modelName );
        $detailsArray   = $model->details; //get array $details di basicModel
        if(isset($data[0]) && is_array($data[0])){
            foreach ($data as $i => $isiData){
                foreach( $isiData as $key => $value ){
                    if(is_array($value) && count($value)>0 && $this->checkDetailExist($key, $detailsArray) ){
                        $this->is_model_exist($key);
                        $this->is_data_required($key, $value);
                        $this->is_data_valid($key,$value);
                        $this->is_detail_valid($key,$value);
                    }
                }
            }
        }else{
            foreach( $data as $key => $value ){
                if(is_array($value) && count($value)>0 && $this->checkDetailExist($key, $detailsArray) ){
                    if( $this->isPatch ){
                        abort(403, json_encode([
                            'message'=>"PATCH tidak boleh mengirimkan detail",
                            "errors"=>[],
                            "resource"=>$modelName
                        ]));
                    }
                    $this->is_model_exist($key);
                    $this->is_data_required($key, $value);
                    $this->is_data_valid($key,$value);
                    $this->is_detail_valid($key,$value);
                }
            }
        }
        $model = null;
    }
    private function is_model_deletable($modelName, $refId )
    {
        if( !in_array($this->operation,["delete"]) ){return true;}
        $modelNameExplode = explode('.', $modelName);
        $model          = Api::getCustom( (count($modelNameExplode)==1?$modelName:$modelNameExplode[1]) );
        $detailsArray   = $model->details; 
        $heirs          = $model->heirs; 
        $cascade        = $model->cascade;
        $deleteable     = $model->deleteable;
        $deleteOnUse    = isset($model->deleteOnUse)?$model->deleteOnUse:false;
        if(!$deleteable){
            $this->messages[] = "UNDELETABLE: cannot delete [$modelName]";
            $this->isAuthorized=false;
            return false;
        }
        if(!$deleteOnUse){
            foreach( $heirs as $heir ){
                $heirExplode = explode('.', $heir);
                // $modelCandidateHeir = "\App\Models\CustomModels\\".(count($heirExplode)==1?$heir:$heirExplode[1]);
                // $modelHeir          = new $modelCandidateHeir;
                $modelHeir          = Api::getCustom( (count($heirExplode)==1?$heir:$heirExplode[1]) );
                $join               = $modelHeir->joins;
                foreach($join as $relation){
                    if(strpos($relation,"$modelName.")!==false){
                        $colArr = explode("=", $relation)[1];
                        $col    = $colArr;
                        $existing = $modelHeir->where($col, $refId )->withoutGlobalScopes()->limit(1)->get();
                        if(count($existing)>0){
                            abort(422, json_encode([
                                'message'=>"Maaf data telah terintegrasi dengan data lain, tidak dapat dihapus",
                                "errors"=>[
                                    "USED: cannot delete id $refId in [$modelName]. It is being used in child $heir"
                                ],
                                "resource"=>$modelName
                            ]));
                        };
                    }
                }
            }
        }
        // if($cascade){
        //     foreach( $detailsArray as $detail ){
        //         $detailData = DB::table($detail)->where;
        //         $this->is_model_deletable($detail, $id);
        //     }
        // }
        $model = null;
        return true;
    }
    private function createAdditionalData($model, $arrayData) // inject CustomModels -> $autoCreate Array
    {
        $fixedArray  = [];
        $dataKey     = $this->operation."AdditionalData";
        if($model->$dataKey){
            $arrayCreate = $model->$dataKey;
            foreach($arrayCreate as $key => $data){
                if(!in_array($key,$model->columns)){
                    $this->messages[] = "NOT ADDED: field $key in $dataKey cannot be add in [".$model->getTable()."]";
                    continue;
                }
                if(strpos($data, '[') !== false){
                    $function = $this->operation."_$key";
                    $newData = $model->$function((object)$arrayData);
                }elseif(strpos($data, 'auth:') !== false){ // operasi untuk mendapatkan auth
                    $authKey = str_replace("auth:", "", $data);
                    $newData = Auth::user()->$authKey;
                }elseif(strpos($data, 'request:') !== false){ // operasi untuk mendapatkan auth
                    $arrayDataKey = str_replace("request:", "", $data);
                    $newData = $arrayData[$arrayDataKey];
                }else{
                    $newData = $data;
                }
                $fixedArray [$key] = $newData;
            }
        }
        return $fixedArray;
    }
    private function createEliminationData($model, $arrayData)
    {
        $columns = $model->columns;
        $fixedArray = [];
        $dropped = [];
        $dataKey     = $this->operation."able";
        foreach($arrayData as $key => $value){
            if(in_array($key, $columns)){
                $fixedArray [$key] = $value;
            }else{
                $dropped []=$key;
                $this->messages[] = "DROPPED: field $key was dropped in ".$model->getTable();
            }
        }
        $createableColumns = $model->$dataKey;
        foreach($fixedArray as $key => $value){
            if(!in_array($key, $createableColumns)){
                unset($fixedArray [$key]);
                $this->messages[] = "WARNING: field $key is not $dataKey in ".$model->getTable();
            }
        }
        return $fixedArray;
    }
    public function createOperation( $modelName, $data, $parentId=null, $parentName=null )
    {
        if(!$this->operationOK){return;}
        $model          = Api::getCustom($modelName);
        $preparedModel = Api::getBasic($modelName);
        $detailsArray   = $model->details;
        
        if(isset($data[0]) && is_array($data[0])){
            foreach ($data as $i => $isiData){
                config([
                    'operating'=>[
                        'type'      => 'create',
                        'detail'    => $modelName,
                        'index'     => isset( $isiData['_additionalIdx'] ) ? $isiData['_additionalIdx'] : $i
                    ]
                ]);
                $isiData["_seq"] = $i;
                $additionalData = $this->createAdditionalData($model, $isiData);
                $eliminatedData = $this->createEliminationData($model, $isiData);
                $processedData  = array_merge($eliminatedData, $additionalData);
                if($parentId && $parentName){
                    $columns    = $model->columns;                   
                    $fkName = $parentName;  
                    $tableSingleArray = explode(".", $parentName);
                    if( count($tableSingleArray)>1){
                        $fkName = $tableSingleArray[1];
                    }
                    if(!in_array($fkName."_id",$columns)){
                        $realJoins = $model->joins;
                        foreach($realJoins as $val){
                            $valArray = explode("=",$val);
                            if($valArray[0]==$fkName.".id"){
                                $fkName = explode(".",$valArray[1])[1];
                                break;
                            }
                        }
                    }else{
                        $fkName.="_id";
                    }
                    $processedData[$fkName] = $parentId;
                }
                $createBeforeEvent = $model->createBefore($model, $processedData, $this->requestMeta);
                if(isset($createBeforeEvent['errors'])){
                    $this->operationOK=false;
                    $this->errors = $createBeforeEvent['errors'];
                    return;
                }
                $finalData  = $createBeforeEvent["data"];
                $finalData  = Api::reformatData($finalData,$model);

                $finalModel = $preparedModel->create( $finalData );
                $model->createAfter($finalModel, $isiData, $this->requestMeta, $finalModel->id);
                $this->success[] = "SUCCESS: data created in ".$model->getTable()." new id: $finalModel->id";
                foreach( $isiData as $key => $value ){
                    if(is_array($value) && count($value)>0 && $this->checkDetailExist($key, $detailsArray) ){
                        if(!$this->is_data_required($key, $value,"create")){ 
                            $this->operationOK=false;
                            return;
                        };
                        if(!$this->is_data_valid($key, $value,"create")){ 
                            $this->operationOK=false;
                            return;
                        };
                        $this->createOperation($key, $value,$finalModel->id, $modelName);
                    }
                }
            }
        }else{
            $additionalData = $this->createAdditionalData($model, $data);
            $eliminatedData = $this->createEliminationData($model, $data);
            $processedData  = array_merge($eliminatedData, $additionalData);
            if($parentId && $parentName){
                $columns    = $model->columns;
                $fkName     = $parentName;
                if(!in_array($fkName."_id",$columns)){
                    $realJoins = $model->joins;
                    foreach($realJoins as $val){
                        $valArray = explode("=",$val);
                        if($valArray[0]==$fkName.".id"){
                            $fkName = explode(".",$valArray[1])[1];
                            break;
                        }
                    }
                }else{
                    $fkName.="_id";
                }
                $processedData[$fkName] = $parentId;
            }
            $createBeforeEvent = $model->createBefore($model, $processedData, $this->requestMeta);
            if(isset($createBeforeEvent['errors'])){
                if($this->isBackdoor){
                    return $createBeforeEvent['errors'];
                }
                $this->operationOK=false;
                $this->errors = $createBeforeEvent['errors'];
                return;
            }
            $finalData  = $createBeforeEvent["data"];
            if($this->isMultipart){
                $req = $this->originalRequest;
                foreach( array_keys($req->all()) as $keyName){
                    if($req->hasFile($keyName) && in_array($keyName, array_keys($finalData)) ){
                        $validator = Validator::make($req->all(), [
                            $keyName => 'max:25000|mimes:pdf,doc,docx,xls,xlsx,odt,odf,zip,tar,tar.xz,tar.gz,rar,jpg,jpeg,png,bmp,mp4,mp3,mpg,mpeg,mkv,3gp'
                        ]);
                        if ( $validator->fails()) {
                            //  #invalid data
                            abort(422, json_encode([
                                'message'=>"Maaf data belum valid, silahkan dikoreksi",
                                "errors"=>$validator->errors(),
                                "resource"=>$modelName
                            ]));
                        }

                        $fileName = Api::sanitizeString($req->$keyName->getClientOriginalName());
                        if(!File::exists(storage_path( "app/public/$modelName" ))){
                            umask(0000);
                            File::makeDirectory( storage_path( "app/public/$modelName" ), 493, true);
                        }
                        $now = Carbon::now()->format('his');
                        $fixedPath = "$modelName/$now-$keyName-{$fileName}";
                        $fixedFullPath = storage_path( "app/public/$fixedPath" );
                        File::put( $fixedFullPath, File::get( $req->$keyName->getRealPath() ) );
                        $finalData[$keyName] = $fixedPath;
                    }
                }
            }
            
            $finalData  = Api::reformatData($finalData,$model);

            if( $parentId && !$parentName ){
                $finalData['id'] = $parentId;
            }

            $finalModel = $preparedModel->create( $finalData );

            if( @$model->extendedTable ){
                $this->createOperation( $model->extendedTable, $data, $finalModel->id );
            }

            $model->createAfter($finalModel, $data, $this->requestMeta, $finalModel->id);
            $this->operationId=$finalModel->id;
            $this->success[] = "SUCCESS: data created in ".$model->getTable()." new id: $finalModel->id";
            foreach( $data as $key => $value ){
                if(is_array($value) && count($value)>0 && $this->checkDetailExist($key, $detailsArray) ){    
                    $tableSingle = $model->getTable();  
                    $tableSingleArray = explode(".", $model->getTable());
                    if( count($tableSingleArray)>1){
                        $tableSingle = $tableSingleArray[1];
                    }
                    if(!$this->is_data_required($key, $value,"create")){ 
                        $this->operationOK=false;
                        if($this->isBackdoor) { abort(422,json_encode(["message"=>"Invalid Data", "errors"=>$this->errors])); }
                        return;
                    };
                    if(!$this->is_data_valid($key, $value,"create")){ 
                        $this->operationOK=false;
                        if($this->isBackdoor) { abort(422,json_encode(["message"=>"Invalid Data", "errors"=>$this->errors])); }
                        return;
                    };
                    $this->createOperation($key, $value, $finalModel->id, $model->getTable());
                }
            }
        }
        if($this->isBackdoor && $parentId==null){
            if(method_exists($model, "createAfterTransaction")){
                $newData = $this->readOperation( $modelName, (object)[], $finalModel->id )['data'];
                $newfunction = "createAfterTransaction";
                $model->$newfunction( 
                    $newData,
                    [], 
                    $this->requestData,
                    $this->requestMeta
                );
            }
            return [
                "status"=> "Data has been created successfully",
                "model" => $modelName,
                "id"    => $finalModel->id
            ];
        }
        $model = null;   
    }
    public function readOperation( $modelName, $params=null, $id=null )
    {
        $params=(array)$params;
        if( $id && isset($params['simplest']) && $params['simplest']=='true' ){
            $model = Api::getBasic($modelName);
            if( isset($params['selectfield']) ){
                $selectField = str_replace([
                        "this.","\n","\t"
                    ],[
                        $model->getTable().".", "", ""
                    ],
                    $params['selectfield']
                );
                $model = $model->selectRaw( $selectField );
            }
            $data = [
                "data"=>$model->withoutGlobalScopes()->find($id)
            ];
            if( env("RESPONSE_FINALIZER") ){
                $funcArr = explode(".", env("RESPONSE_FINALIZER"));
                $class = getCore($funcArr[0]) ?? Api::getCustom($funcArr[0]);
                $func = $funcArr[1];
                $data = $class->$func( $data, $modelName );
            }
            $data['processed_time'] = round(microtime(true)-config("start_time"),5);
            return $data;
        }
        foreach($params as $key => $param){
            if(is_array($param)){
                continue;
            }
            if( str_replace(["null","NULL"," "],["","",""],$param)==""){
                $params[$key] = null;
            }
        }
        $p=(object)$params;
        $model          = Api::getCustom( $modelName );
        config( [ "parentTable" => $model->getTable() ] );
        
        if($id!=null){
            $p->id          = $id;
            $p->joinMax     = 0;
            $data = [
                "data"=>$model->customFind($p)
            ];
            if( env("RESPONSE_FINALIZER") ){
                $funcArr = explode(".", env("RESPONSE_FINALIZER"));
                $class = getCore($funcArr[0]) ?? Api::getCustom($funcArr[0]);
                $func = $funcArr[1];
                $data = $class->$func( $data, $modelName );
            }
            $data['processed_time'] = round(microtime(true)-config("start_time"),5);
            return $data;
        }else{
            $p->joinMax     = 0;
            $p->caller      = null;
            return $model->customGet($p);
        }
    }
    public function deleteOperation( $modelName, $params=null, $id=null, $fk=null )
    {
        $modelNameExplode = explode('.', $modelName);
        $model          = Api::getCustom( (count($modelNameExplode)==1?$modelName:$modelNameExplode[1]) );
        if( !is_numeric($id) && $model->useEncryption ){
            $id = $model->decrypt( $id );
        }
        $table          = $model->getTable();
        $detailsArray   = $model->details; 
        $cascade        = $model->cascade;
        $preparedModel  = $model->withoutGlobalScopes()->find($id);
        if(!$preparedModel){
            abort(404, json_encode([
                'message'=>"Maaf Data tidak ditemukan",
                'resource'=>$modelName,
                'id' => $id
            ]));
        }
        
        $deleteBeforeEvent = $model->deleteBefore($preparedModel, $preparedModel, $this->requestMeta, $id);        
        if(isset($deleteBeforeEvent['errors'])){
            $this->operationOK=false;
            $this->errors = $deleteBeforeEvent['errors'];
            return;
        }
        
        $this->requestData = $preparedModel;
        
        if( count( $model->fileColumns ) > 0 ){
            if( !config( 'files_to_remove' ) ){
                config([ 'files_to_remove' => [] ]);
            }
            $oldArr = config( 'files_to_remove' );
            foreach( $model->fileColumns as $col ){
                if( $preparedModel->$col ){
                    $oldArr[] =  $preparedModel->getRawOriginal($col);
                }
            }
            config([ 'files_to_remove' => $oldArr ]);
        }

        $preparedModel->delete();
        
        if( @$model->extendedTable ){
            Api::getBasic( $model->extendedTable )->where('id', $id)->delete();
        }

        $model->deleteAfter($model, $preparedModel, $this->requestMeta, $id);
        
        $this->success[] = "SUCCESS: data deleted in $table id: $id";
        
        if($cascade){
            foreach( $detailsArray as $detail ){
                $detailsExplode = explode('.', $detail);
                // $modelCandidate = "\App\Models\CustomModels\\".(count($detailsExplode)==1?$detail:$detailsExplode[1]);
                // $model          = new $modelCandidate;
                $model          = Api::getCustom( (count($detailsExplode)==1?$detail:$detailsExplode[1]) );
                $tableExplode = explode('.', $table);
                $dataDetail = $model->where((count($tableExplode)==1?$table:$tableExplode[1])."_id","=",$id)->withoutGlobalScopes()->get();              
                
                foreach( $dataDetail as $idxDtl => $dtl ){
                    if(!$this->is_model_deletable( $detail, $dtl->id )){
                        $this->operationOK = false;
                        return;
                    };

                    if(!$this->is_operation_authorized( $detail, $dtl->id )){
                        $this->operationOK = false;
                        return;
                    }

                    config([
                        'operating'=>[
                            'type'      => 'delete',
                            'detail'    => $detail,
                            'index'     => $idxDtl
                        ]
                    ]);
                    $this->deleteOperation($detail, null, $dtl->id, $id);
                }
            }
        }
        $model = null;
    }
    public function updateOperation($modelName, $data=null, $id=null)
    {
        if(!$this->operationOK){return;}
        $model          = Api::getCustom( $modelName );
        if( !is_numeric($id) && $model->useEncryption ){
            $id = $model->decrypt( $id );
        }
        $detailsArray   = $model->details; 
        $cascade        = $model->cascade;
        $preparedModel  = Api::getBasic($modelName)->withoutGlobalScopes()->find($id);
        if(!$preparedModel){
            abort(404, json_encode([
                'message'=>"Maaf Data tidak ditemukan",
                'resource'=>$modelName,
                'id'=>$id
            ]));
        }
        $additionalData = $this->createAdditionalData($model, $data);
        $eliminatedData = $this->createEliminationData($model, $data);
        $processedData  = array_merge($eliminatedData, $additionalData);
        $updateBeforeEvent = $model->updateBefore($preparedModel, $processedData, $this->requestMeta,$id);
        if(isset($updateBeforeEvent['errors'])){
            if($this->isBackdoor){
                return $updateBeforeEvent['errors'];
            }
            $this->operationOK=false;
            $this->errors = $updateBeforeEvent['errors'];
            return;
        }
        $finalData  = $updateBeforeEvent["data"];
        if($this->isMultipart){
            $req = $this->originalRequest;
            $oldArrToRemoves = config( 'files_to_remove' );
            foreach( array_keys($req->all()) as $keyName){
                if($req->hasFile($keyName) && in_array($keyName, array_keys($finalData)) ){
                    $validator = Validator::make($req->all(), [
                        $keyName => 'max:25000|mimes:pdf,doc,docx,xls,xlsx,odt,odf,zip,tar,tar.xz,tar.gz,rar,jpg,jpeg,png,bmp,mp4,mp3,mpg,mpeg,mkv,3gp'
                    ]);
                    if ( $validator->fails() ) {
                        //  #invalid data
                        abort(422, json_encode([
                            'message'=>"Maaf data belum valid, silahkan dikoreksi",
                            "errors"=>$validator->errors(),
                            "resource"=>$modelName
                        ]));
                    }

                    $fileName = Api::sanitizeString($req->$keyName->getClientOriginalName());
                    if(!File::exists(storage_path( "app/public/$modelName" ))){
                        umask(0000);
                        File::makeDirectory( storage_path( "app/public/$modelName" ), 493, true);
                    }
                    $now = Carbon::now()->format('his');
                    $fixedPath = "$modelName/$now-$keyName-{$fileName}";
                    $fixedFullPath = storage_path( "app/public/$fixedPath" );
                    File::put( $fixedFullPath, File::get( $req->$keyName->getRealPath() ) );
                    $finalData[$keyName] = $fixedPath;
                    $oldArrToRemoves[] = $preparedModel->getRawOriginal($keyName);
                }
            }
            config([ 'files_to_remove' => $oldArrToRemoves ]);
        }

        $finalData  = Api::reformatData($finalData,$preparedModel);
        
        $finalModel = $preparedModel->update($finalData);
        
        if( @$model->extendedTable ){
            $this->updateOperation( $model->extendedTable, $data, $id );
        }

        $model->updateAfter($finalModel, $processedData, $this->requestMeta, $id);
        $this->success[] = "SUCCESS: data update in ".$model->getTable()." id: $id";                
        
        foreach( $detailsArray as $detail ){
            if( !$this->checkDetailExist($detail,array_keys($data)) ){
                continue;
            }
            $detailClass = $detail;
            $detailArray = explode( ".", $detail);
            if( count( $detailArray )>1 ){
                $detailClass = $detailArray[1];
            }
            
            $modelChild          = Api::getCustom( $detailClass );            
            $detailIds = [];
            $detailNew = [];
            $detailOld = [];
            $columns    = $modelChild->columns;
            $fkName     = $model->getTable();
            if(!in_array($fkName."_id",$columns)){
                $realJoins = $modelChild->joins;
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
            foreach($data[$detailClass] as $index => $valDetail){
                $isChildOfParent = false;
                if( isset($valDetail['id']) ){
                    $valDetail['id'] = !is_numeric($valDetail['id']) && $modelChild->useEncryption? $modelChild->decrypt( $valDetail['id'] ): $valDetail['id'];
                    $isChildOfParent = $modelChild->where($fkName,$id)->where('id', $valDetail['id'])->exists();
                }

                if($isChildOfParent){

                    if( !$this->is_operation_authorized($detailClass, $valDetail['id']) ){
                        $this->operationOK = false;
                        return;
                    }

                    config([
                        'operating'=>[
                            'type'      => 'update',
                            'detail'    => $detail,
                            'index'     => $index
                        ]
                    ]);
                    
                    $this->updateOperation($detailClass, $valDetail, $valDetail['id']);

                    $detailIds[]=$valDetail['id'];
                    $detailOld [] = $valDetail;
                }else{
                    $valDetail['_additionalIdx'] = $index;
                    $detailNew [] = $valDetail;
                }
            };
            
            $dataDetail = $modelChild->where($fkName,$id)->whereNotIn('id',$detailIds)->withoutGlobalScopes()->get();                
            foreach( $dataDetail as $dtl ){
                $oldOperation = $this->operation;
                $this->operation = 'delete';
                if( !$this->is_operation_authorized($detailClass, $dtl->id) ){
                    $this->operationOK = false;
                    return;
                }
                $this->operation = $oldOperation;

                $this->deleteOperation($detailClass, null, $dtl->id, $id);
            }
            if( count($detailNew)>0){
                if(!$this->is_data_required($detailClass, $detailNew,"create")){ 
                    $this->operationOK=false;
                    return;
                };
                if(!$this->is_data_valid($detailClass, $detailNew,"create")){ 
                    $this->operationOK=false;
                    return;
                };
                $oldOperation = $this->operation;
                $this->operation = 'create';
                if( !$this->is_operation_authorized( $detailClass ) ){
                    $this->operationOK = false;
                    return;
                }
                $this->operation = $oldOperation;

                $this->createOperation($detailClass, $detailNew, $id, $model->getTable()); //jeregi
            }
            // foreach($detailOld as $oldDetail){
            //     $this->updateOperation($detail, $oldDetail, $oldDetail['id']);
            // }
        }
        // foreach( $data as $key => $value ){
        //     if(is_array($value) && count($value)>0 && in_array($key, $detailsArray) ){
        //         $detailIds = [];
        //         foreach($data[$key] as $valDetail){
        //             if(isset($valDetail['id']) && is_numeric($valDetail['id'])){
        //                 $detailIds[]=$valDetail['id'];
        //             }
        //         };
        //         $this->createOperation($key, $value, $id, $model->getTable());
        //     }
        // }
        
        if($this->isBackdoor){
            if(method_exists($model, "updateAfterTransaction")){
                $newData = $this->readOperation( $modelName, (object)[], $id )['data'];
                $newfunction = "updateAfterTransaction";
                $model->$newfunction( 
                    $newData,
                    $newData, 
                    $this->requestData,
                    $this->requestMeta
                );
            }
            return [
                "status"=>"Data has been updated successfully",
                "model" => $modelName,
                "id"=>$id
            ];
        }
        $model = null;  
    }
    public function router(Request $request, $modelname, $id=null, $detailmodelname=null, $detailid=null, $subdetailmodelname=null, $subdetailid=null )
    {
        if( $detailmodelname ){
            $modelname = $detailmodelname;
            config(['parent_lv1_id'=>$id]);
            $id = $detailid;
        }
        
        if( $subdetailmodelname ){
            $modelname = $subdetailmodelname;
            config(['parent_lv2_id'=>$detailid]);
            $id = $subdetailid;
        }

        if( $this->operation==='read' && !$id ){
            $this->operation = 'list';
        }
        
        if($this->isAuthorized){
            if($this->customOperation){
                $function = env('FUNCTION_API_PREFIX', "action").$this->operationId;
                $functionName = $this->operationId;
                // $modelCandidate = "\App\Models\CustomModels\\$this->parentModelName";
                // $model = new $modelCandidate;
                $model = Api::getCustom($modelname);
                if( !method_exists( $model, $function ) ){
                    abort(404, json_encode([
                        'message'=>"Maaf, action ini tidak ditemukan",
                        "action" => Str::replaceFirst('action','',$function),
                        "resource"=>$modelname
                    ]));
                }
                $result = $model->$function(  $request );
                return $result;
            }

            if( in_array($this->operation, ['read','list']) ){
                return $this->readOperation($modelname, $this->requestData,$id);
            }


            DB::beginTransaction();
            $model = Api::getCustom($modelname);
            
            if( method_exists($model, $this->operation."AfterTransaction") && $this->operationId!==null ){
                $oldData = $this->readOperation( $this->parentModelName, (object)[], $this->operationId )['data'];
            }
            $function = $this->operation."Operation";
            if( method_exists($model, $function) ){
                if( $this->operation=='create' ){
                    $overrideOperation = $model->$function( $this->originalRequest );
                }elseif( $this->operation=='update' ){
                    $overrideOperation = $model->$function( $this->originalRequest, $id );
                }
                if(is_array($overrideOperation)){
                    return response()->json($overrideOperation,400);
                }
            }else{
                $this->$function($this->parentModelName,$this->requestData, $id);
                if(!$this->operationOK){
                    DB::rollback();
                    return response()->json([
                        "message"    => Str::title($this->operation)." Data failed",
                        // "warning"  => $this->messages, 
                        "success"  => $this->success, 
                        "errors"  => $this->errors, 
                        // "request" => $this->requestData,
                        // "id"      => $this->operationId,
                        "processed_time"=>round(microtime(true)-config("start_time"),5)
                    ],400);
                }
            }

            if($this->operationOK){
                if(method_exists($model, $this->operation."AfterTransaction")){
                    $newData = $this->readOperation( $this->parentModelName, (object)[], $this->operationId )['data'];
                    $newfunction = $this->operation."AfterTransaction";
                    $model->$newfunction( 
                        $newData,
                        isset($oldData)?$oldData:[], 
                        $this->requestData,
                        $this->requestMeta
                    );
                }

                DB::commit();
                $responses = [
                    // "operation" => $this->operation,
                    "message"    => Str::title($this->operation)." Data sukses", 
                    // "warning"  => $this->messages, 
                    "success"  => $this->success, 
                    "errors"  => $this->errors,
                    // "request" => $this->requestData,
                    "id"      => $this->operationId,
                ];
                if(method_exists($model, "transformResponse")){
                    $newResponses = $model->transformResponse($responses);
                    if( gettype($newResponses)=='array' ){
                        $responses=$newResponses;
                    }
                }
                $responses["processed_time"] = round(microtime(true)-config("start_time"),5);
                if(config('files_to_remove')){
                    foreach( config( 'files_to_remove' ) as $path ){
                        if( File::exists( storage_path( "app/public/$path" ) ) ){
                            File::delete( storage_path( "app/public/$path" ) );
                        }
                    }

                }

                return response()->json($responses,200);
            }else{
                DB::rollback();
                Logger::store($e);
                return response()->json([
                    "message"    => Str::title($this->operation)." Data gagal",
                    // "warning"   => $this->messages, 
                    "success"   => $this->success, 
                    "errors"  => $e->getMessage().(env("APP_DEBUG",false)?" => file: ".$e->getLine().". line: ".$e->getLine():""),
                    // "request"   => $this->requestData,
                    "id"        => $this->operationId,
                    "processed_time"=>round(microtime(true)-config("start_time"),5)
                ],422);
            }
        }else{
            DB::rollback();
            return response()->json([
                "message"    => "$this->operation tidak diizinkan",
                // "warning"  => $this->messages, 
                "success"  => $this->success, 
                "errors"  => $this->errors, 
                // "request" => $this->requestData,,
                "processed_time"=>round(microtime(true)-config("start_time"),5),
                "id"        => $this->operationId
            ],422);
        }
    }
}