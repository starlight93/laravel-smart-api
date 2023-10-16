<?php

namespace Starlight93\LaravelSmartApi\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Jfcherng\Diff\DiffHelper;
use Carbon\Carbon;
use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;

class EditorFunc {

    public static function __callStatic($method, $args): mixed
    {
        return (new static)->$method(...$args);
    }

    public static function getDriver() : string // 'pgsql','mysql','sqlsever','sqlite'
    {
        return Schema::getConnection()->getDriverName();
    }
    
    public static function putFileDiff( $path, $text )
    {
        $now = Carbon::now()->format( 'Y-m-d' );
        $oldFile = File::exists( $path ) ? File::get( $path ): '';
        File::put( $path, $text );

        if( !$oldFile ) return null;
        
        $keyArr = explode('/', $path);
        $key = end($keyArr);
        $value = Cache::get( $key );
        
        if( !$value || $value['last_update']!= $now || ($value['last_editor']!=config('developer') && $value['last_update']== $now) ){
            Cache::put( $key, [
                "last_update" =>  $now,
                "last_editor" => config('developer'),
                "content" => $oldFile
            ], 86400);
        }else{
            $oldFile = $value['content'];
        }
        return DiffHelper::calculate( $oldFile, $text, 'Json' );
    }

    public static function devTrack( $action, $fileName, $fileDiff=null )
    {
        if( gettype($fileDiff)=='string' && $fileDiff=='[]' ) return;
        $key = "developer_activities";
        $activities = Cache::get( $key ) ?? [];
        $foundToday = null;
        $foundIdx = null;
        $now = Carbon::now()->format('Y-m-d');
    
        foreach( $activities as $idx => $act ){
            if( $act['file']==$fileName && $act['action']==$action && Str::startsWith($act['time'], $now) ){
                if( $act['name']==config('developer') ){
                    $foundToday = $act;
                    $foundIdx = $idx;
                }
                break;
            }
        }
        
        if( $foundToday ){
            unset( $activities [ $foundIdx ] );
        }
    
        array_unshift($activities, [
            'id' => strtotime('now').uniqid(),
            'time' => Carbon::now()->format('Y-m-d H:i:s'),
            'name' => config('developer'),
            'action' => $action,
            'file' => $fileName,
            'diff' => $fileDiff,
            'ip' => app()->request->ip()
        ]);
        
        Cache::forever( $key, array_slice( $activities, 0, env('DEV_ACTIVITIES_MAX_ROWS', 250), true) );
    }

    public static function table_config( $table, $array )
    {
        $string = json_encode($array);
        if(self::getDriver()=='mysql'){
            Schema::getConnection()->statement("ALTER TABLE $table comment = '$string'");
        }elseif(self::getDriver()=='pgsql'){
            Schema::getConnection()->statement("COMMENT ON TABLE $table IS '$string'");
        }
    }

    /* Digunakan untuk send notify ke frontend (untuk hot-reload)*/
    public static function wssNotify( string $type='notify', mixed $message=null )
    {
        $socketServer = env('LOG_SENDER');
        $clientChannel = env('CLIENT_CHANNEL');
        if( !$socketServer || !$clientChannel ) return false;
    
        $url = "$socketServer/$clientChannel";
        $payloads = [
            "data"=>[
                "_type"=> $type,
                "message"=>[
                    "msg"=> $message
                ]
            ]
        ];
        
        try{
            $client = Http::withHeaders([]);
            return $client->asForm()->post($url, $payloads)->json();
        }catch(\Exception $e){
            return false;
        }
    }

    public static function getDeveloperActivities( bool $html=true ): mixed
    {
        $activities = Cache::get( "developer_activities" )??[];
        if( !$html ) return $activities;
        $htmlData = "";
        $count = 0;
        
        foreach( $activities as $idx => $act ){
            if(@$act['diff']==='[]') continue;
            $count++;
            $fileUrl = $act['file'];
            if(@$act['diff'] && @$act['id']){
                $fileUrl = "<a href='/docs/activities/{$act['id']}'>{$act['file']}</a>";
            }
            $dev = ( @(explode('-',$act['name'], 2)[1]) ?? '*' ).( @$act['ip'] ? " [ ".$act['ip']." ]":"" );
            $row="<tr><td style='text-align:center;'>$count</td><td style='text-align:center;'>{$act['time']}</td><td>$dev</td><td>{$act['action']}</td>
            <td>$fileUrl</td></tr>";
            $htmlData.=$row;
        }

        return "<h3 style='text-align:center'> Dev Activities on ".env('APP_NAME')." until ".(Carbon::now()->format('d/m/Y')).'</h3><table style="width:100%;" border="1" cellpadding=1>
                <thead style="background:pink;"><th>No</th><th>Time</th><th>Developer</th><th>Action</th><th>Relation</th></thead>'.
                "<tbody>$htmlData</tbody></table>";
    }

    public static function getBasic($name)
    {
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

    public static function getTest($filename=null , $string=false){
        if( !$filename ){
            $dtrace = (object)debug_backtrace(1,true)[0];
            $filenameArr = explode(php_uname('s')=='Linux'?"/":"\\",$dtrace->file);
            $filename = str_replace(".php",".json",end($filenameArr));
        }

        $table = self::getBasic( $filename )->getTable();
        $filename = Str::camel(ucfirst($filename));
        $pathPrefix = Api::isLumen() ? "tests/$filename" :"tests/Feature/$filename";
        $path = base_path($pathPrefix."Test.php");
        if( ! File::exists($path) ){
            return str_replace( [
                "___class___","__table__","__resource__","__prefix__"
            ],[
                $filename, $table, $filename, config("api.route_prefix")
            ],File::get( self::lib_path("templates/".(Api::isLumen()?'LumenTest':'LaravelTest').".stub") ) );
        }
        if($string){
            return File::get($path);
        }
        return File::get($path);
    }

    public static function lib_path( string $path ): string
    {
        return base_path( "vendor/starlight93/laravel-smart-api/$path" );
    }

    public static function ff( mixed $data, mixed $id="")
    {
        $channel = env("LOG_PATH", env('APP_NAME',uniqid()));
        $client = new \GuzzleHttp\Client();
        $socketServer = env("LOG_SENDER");
        try{
            if(!in_array(gettype($data),["object","array"])){
                $data = [$data];
            }
            $dtrace = (object)debug_backtrace(1,true)[0];
            $data = is_object($data)?array($data):$data;
            $filename = explode("/",$dtrace->file);
            $data = array_merge($data,[ "debug_id"=>$id." [".str_replace(".php","",end($filename)).":$dtrace->line]"."~".Carbon::now('GMT+7')->format('H:i:s') ]);        
            $client->post(
                "$socketServer/$channel",
                [
                    'form_params' => $data
                ]
            );
        }catch(\Exception $e){
            $client->post(
                "$socketServer/$channel", [
                    'form_params' => ["debug_error"=>$e->getMessage(),"debug_id"=>$id]
                ]
            );
        }
    }
}