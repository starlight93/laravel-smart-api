<?php

namespace Starlight93\LaravelSmartApi\Casts;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class Upload implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        if( !$value || Str::startsWith( Str::lower($value), "http") ) return $value;
        return url( "storage/$value" );
    }

    public function set($model, $key, $value, $attributes)
    {
        if(Str::contains(app()->request->header("Content-Type"),"multipart")){
            return $value;    
        }
        
        $oldValue = $model->getRawOriginal($key);
        $custom = \Api::getCustom( \Api::getTableOnly( $model->getTable() ) );
        if( count($custom->fileColumns)>0 && in_array($key, $custom->fileColumns) ){
            $modelName = \Api::getTableOnly( $model->getTable() );

            if( $oldValue ){
                if(Str::contains($value, "$modelName/") || Str::startsWith( Str::lower($value), "http")){
                    if( Str::startsWith($value, url( "storage" ) ) ) return Str::replace(url( "storage" )."/",'', $value);
                    return $value;
                }

                $value = $this->saveFromCache( $modelName, $key, $value );
                $this->removeOldFile( $oldValue );
            }else{
                $value = $this->saveFromCache( $modelName, $key, $value );
            }
        }
        return $value;
    }

    protected function removeOldFile( string $path ){
        $fixedPath = storage_path( "app/public/$path" );
        if( File::exists( $fixedPath ) ){
            File::delete( $fixedPath );
        }
    }

    protected function saveFromCache( $dirPath, $field, $value ){
        if( Str::startsWith($value, 'temp-') ){
            if( !($cacheContent = Cache::pull($value)) ){
                abort(422, json_encode([ 
                    'message' => "File perlu diupload ulang", "errors"=>[ $field => [ 'File perlu diupload' ] ] 
                ]) );
            }

            $content = base64_decode( $cacheContent );
            if(!File::exists(storage_path( "app/public/$dirPath" ))){
                umask(0000);
                File::makeDirectory( storage_path( "app/public/$dirPath" ), 493, true);
            }
            $now = Carbon::now()->format('his');
            $fixedPath = "$dirPath/$now-$field-".(Str::replace('temp-', '', $value));
            $fixedFullPath = storage_path( "app/public/$fixedPath" );
            File::put( $fixedFullPath, $content );
            return $fixedPath;
        }
    }
}