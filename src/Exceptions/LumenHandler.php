<?php

namespace Starlight93\LaravelSmartApi\Exceptions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Http\Response;
use Starlight93\LaravelSmartApi\Helpers\Logger;
use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;

class LumenHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        \League\OAuth2\Server\Exception\OAuthServerException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        DB::rollback();
        $rendered = parent::render($request, $e);
        $msg = $this->getFixedMessage($e);
        $statusCode = $rendered ? $rendered->getStatusCode():500;

        if(!$msg && $statusCode==404){
            $msg = 'Endpoint Tidak Ditemukan';
        }

        Logger::store( $e, $statusCode );
        $responseError = [
            'processed_time' => round(microtime(true)-config("start_time"),5)
        ];
        if( is_array($msg) ){
            $responseError = array_merge( $responseError, $msg );
        }else{
            $responseError['message'] = $msg;
        }
        return response()->json($responseError, $statusCode);
    }

    private function getFixedMessage($e){
        if( Api::isJson($e->getMessage()) ){
            return json_decode( $e->getMessage(), true );
        }
        
        if( !env("TUTORIAL",false) || strtolower(env("SERVERSTATUS","OPEN"))=='closed'){
            return $e->getMessage();
        }

        if( !config('app.debug') ){
            $classes = explode( '\\', get_class( $e ) );
            $type = end($classes);
            if( !in_array( $type, ['HttpException']) ){
                return "Oops, internal server error.";
            }
        }

        $fileName = explode( (Str::contains($e->getFile(), "\\")?"\\":"/"), $e->getFile());
        $stringMsg = $e->getMessage();
        $stringMsg = $stringMsg === null || $stringMsg == ""? "Maybe Server Error" : $stringMsg;
        // $stringMsg = !Str::contains( $stringMsg, "SQLSTATE" ) && ( config('app.debug') || !empty( app()->request->header("Debugger") )) ? $stringMsg : "Maybe Server Error";
        $msg = $stringMsg.(config('app.debug')?" => file: ".str_replace(".php","",end($fileName))." line: ".$e->getLine():"");
        return $msg;
    }
}
