<?php

namespace Starlight93\LaravelSmartApi\Helpers;

use Illuminate\Support\Str;
use Illuminate\support\Facades\Auth;

/**
 * Class untuk simple generate decrypt dan encryption
 */
class Cryptor {

    protected $userId;
    protected $token;

    function __construct(){
         $this->userId = Auth::check()?Auth::id():81;
         $this->token  = Auth::check()?explode(' ',app()->request->header('authorization')) [1]:Str::replace(':','',env('APP_KEY'));
    }

    public function encrypt( string|int $id ){
        try{
            $tokenLength = strlen($this->token);

            $rand = random_int(1, $tokenLength-4);
            $randString = substr($this->token, $rand, 4);
            $intRandString = filter_var($randString, FILTER_SANITIZE_NUMBER_INT);

            if(!is_numeric($intRandString)){
                $intRandString = 0;
            }
            $encrypted = $randString. ($id+$this->userId+$intRandString);

            return Str::substrReplace( $encrypted, '_', random_int(0,4), 0);
        }catch(\Exception $e){
            return null;
        }
    }

    public function decrypt( $encrypted, $isSafe = false ){
        if( !Str::contains( $encrypted, "_") ) return $encrypted;
        $encrypted = Str::replace( "_", "", $encrypted );
        $tokenLength = strlen($this->token);
        try{
            $randString = substr($encrypted, 0, 4);
            
            if( !Str::contains( $this->token, $randString ) ){
                return $isSafe? $encrypted : null;
            }
            $intRandString = filter_var($randString, FILTER_SANITIZE_NUMBER_INT);
            if(!is_numeric($intRandString)){
                $intRandString = 0;
            }
            $angka = Str::replace($randString, '', $encrypted);
            if( !is_numeric($angka) || !is_numeric($angka) || !is_numeric($angka) ){
                return $isSafe ? $encrypted : null;
            }
            return $angka-$intRandString-$this->userId;
        }catch(\Exception $e){
            // failed
            return $isSafe ? $encrypted : null;
        }
    }

    public function decryptQuery( $willDecryptString ){
        preg_match_all("/(:\w+:)/", $willDecryptString, $m);
        foreach( $m[0] as $encrypted ){
            $decrypted = $this->decrypt(str_replace( ":", "", $encrypted ), true);
            $willDecryptString = str_replace($encrypted, $decrypted, $willDecryptString);
        }
        return $willDecryptString;
    }
}
