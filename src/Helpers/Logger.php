<?php

namespace Starlight93\LaravelSmartApi\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

//  Digunakan untuk custom Logging
class Logger
{
    /**
     * 
     * @param Exception $err
     * @param int $httpCode
     * 
     * @return void
     * 
     */
    public static function store( $err, $httpCode = 422 ) : void
    {
        $classes = explode( '\\', get_class( $err ) );
        $type = end($classes);
        if( $type=='HttpException' || config('app.debug') || env('APP_DEBUG') || Str::contains(app()->request->fullUrl(),'laradev')){
            return;
        }
        if( !(self::activate()) ){
            return;
        }

        $insertedLogId = DB::table('default_error_logs')->insertGetId( $data = [
            'url' => app()->request->fullUrl(),
            'url_frontend'=> app()->request->header('URLORIGIN'),
            'user_ip'=> app()->request->ip(),
            'modul' => self::getFrontendModul(),
            'username' => \Auth::check()?\Auth::user()->username:null,
            'payload' => json_encode(app()->request->all()),
            'exception_code' => $err->getCode(),
            /**
             * $httpCode maybe should not used anymore
             */
            'http_code'=> $httpCode,
            'error_log'=> $err->getMessage(),
            'file'=> $err->getFile(),
            'line'=> $err->getLine(),
            'method'=> app()->request->method(),
            'type' => $type,
            'created_at'=>\Carbon::now()
        ]);

        $cacheTraceTime = env('ERROR_TRACE_CACHE_SECONDS');
        if( $cacheTraceTime ){
            Cache::put( "log-trace-$insertedLogId", $err->getTrace() ,$cacheTraceTime );
        }

        // self::notify( $data, $insertedLogId );
    }

    public static function trace( int $id ){
        return Cache::get( $id );
    }

    private static function getFrontendModul(){
        return null;
        $menu = app()->request->header('URLORIGIN');
        $modul = DB::select(
            "select modul from dbhr.set_mas_erp_menu where ? ~* url_path LIMIT 1", [$menu]
        );
        return $modul ? $modul[0]->modul : null;        
    }

    //  Release cached trace logs sesuai setting env: ERROR_CACHE_RELEASE_DAYS
    public static function releaseCache(){
        $days = env('ERROR_CACHE_RELEASE_DAYS', 60);
        $mustReleases = DB::table('default_error_logs')
            ->whereRaw(
                "extract(days from (now()-created_at))>$days AND (developer_note IS NULL OR developer_note NOT LIKE '% (CLEARED BY SYSTEM)')"
            )->get();

        foreach($mustReleases as $log){
            Cache::forget( "log-trace-$log->id" ); // clear cached error trace
            DB::table('default_error_logs')->where('id', $log->id)
                ->update([
                    'developer_note'=> $log->developer_note." (CLEARED BY SYSTEM)",
                    'updated_at'=>\Carbon::now()
                ]);
        }

        return count($mustReleases)." cached error traces has been cleared";
    }

    // Untuk Konten Isi bisa diubah sesuai keperluan
    private static function notify( $data, $id ){
        $modul = strtoupper( @$data['modul'] ?? 'other' );
        $mailto = env("BUG_".$modul."_MAILTO");
        
        if( $mailto ){
            try{
                $defaultConfig = include config_path("mail.php");
                configEmail( env('MAIL_FROM_NAME'), (object)$defaultConfig ); // Resetting email to default config (.env)
                $res = SendEmail(
                    $mailto,"#$id BUG ERP $modul",
                    "<p>Type: {$data['type']}</p><p>Accessed URL: {$data['url']}</p><p>Message: {$data['error_log']}</p><p>File: {$data['file']}</p><p>Line: {$data['line']}</p>",
                    $silent=false
                );
                if($res && gettype($res)=='boolean'){
                    DB::table('default_error_logs')->where('id', $id)->update([
                        'status'=>'STORED & NOTIFIED'
                    ]);
                }
            }catch(\Exception $e){
                // other action
            }
        }
    }

    // Mengaktifkan table jika belum ada
    private static function activate(){
        $key = "default_error_logs_is_migrated";
        
        if( Cache::has( $key ) ){
            return true;
        }elseif( !Schema::hasTable('default_error_logs') && File::exists( base_path("database/migrations/__defaults/0_0_0_0_default_error_logs.php") ) ){
            Artisan::call( "migrate:refresh", [
                "--path"=>"database/migrations/__defaults/0_0_0_0_default_error_logs.php" , "--force" => true
            ]);
            Cache::forever( $key, 1 );
        }else{
            return false;
        }
        
        return true;
    }
}