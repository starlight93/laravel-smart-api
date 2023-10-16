<?php

namespace Starlight93\LaravelSmartApi\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use Throwable;

class LaravelHandler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (HttpException $e, Request $request) {
            $errorJson = json_decode($e->getMessage(),true);

            if(!$errorJson && $e->getStatusCode()==401){
                $errorJson = ['message'=>'Unauthenticated'];
            }elseif(!$errorJson && $e->getStatusCode()==404){
                $errorJson = ['message'=>'Endpoint tidak ditemukan'];
            }

            return response()->json(
                array_merge([
                        'processed_time' => round(microtime(true)-config("start_time"),5),
                    ],
                    $errorJson??['message'=>$e->getMessage()]
                ), 
                $e->getStatusCode()
            );
        });
    }
}
