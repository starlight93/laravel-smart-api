<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/databases', 'EditorController@databaseCheck');
Route::post('/databases', 'EditorController@createDatabase');
Route::delete('/databases/{databaseName}', 'EditorController@deleteDatabase');

Route::get('/tables', 'EditorController@readTables');
Route::get('/tables/{table}', 'EditorController@readTables');
Route::put('/tables/{tableName}/trigger', 'EditorController@makeTrigger');
Route::delete('/tables/{tableName}/trigger', 'EditorController@makeTrigger');
Route::post('/tables', 'EditorController@createTables');
Route::delete('/tables/{tableName}', 'EditorController@deleteTables');
Route::post('/migrate', 'EditorController@migrateDefault');

Route::get('/models', 'EditorController@readMigrationsOrCache');
Route::get('/models/{tableName}', 'EditorController@readModelsOne');
Route::post('/models', 'EditorController@createModels');
Route::post('/models/{tableName}', 'EditorController@createModels');
Route::put('/models/{tableName}', 'EditorController@updateModelsOne');
Route::post('/mail', 'EditorController@mail');

Route::get('/migrations', 'EditorController@readMigrations');
Route::get('/logs/{table}', 'EditorController@readLog');
Route::get('/tests/{table}', 'EditorController@readTest');
Route::put('/tests/{table}', 'EditorController@editTest');
Route::get('/alter/{table}', 'EditorController@readAlter');
Route::put('/alter/{table}', 'EditorController@editAlter');
Route::get('/migrations/{table}', 'EditorController@readMigrations');
Route::post('/migrations', 'EditorController@editMigrations');
Route::put('/migrations/{table}', 'EditorController@editMigrations');

Route::get('/realfk', 'EditorController@getPhysicalForeignKeys');
Route::get('/dorealfk', 'EditorController@setPhysicalForeignKeys');

Route::get('/migrate/{table}', 'EditorController@doMigrate');
Route::get('/do-test/{table}', 'EditorController@doTest');
Route::get('/queries10rows/{table}', 'EditorController@queries10rows');
Route::get('/truncate/{table}', 'EditorController@truncate');
Route::get('/refreshalias/{table}', 'EditorController@refreshAlias');

Route::post("/uploadlengkapi","EditorController@uploadLengkapi");
Route::post("/uploadtest","EditorController@uploadTest");
Route::post("/uploadwithcreate","EditorController@uploadWithCreate");
Route::post("/uploadtemplate","EditorController@uploadTemplate");
Route::post("/paramaker","EditorController@paramaker");
Route::post("/run-query","EditorController@runQuery");
Route::get("/run-backup","EditorController@runBackup");

Route::get("/javascript", "EditorController@getJsFile");
Route::get("/javascript/{filename}","EditorController@getJsFile");
Route::put("/javascript/{filename}","EditorController@saveJsFile");
Route::delete("/javascript/{filename}","EditorController@deleteJsFile");

Route::get("/blades", "EditorController@getBladeFile");
Route::get("/blades/{filename}","EditorController@getBladeFile");
Route::put("/blades/{filename}","EditorController@saveBladeFile");
Route::delete("/blades/{filename}","EditorController@deleteBladeFile");
Route::get("/cores", "EditorController@getCoreFile");
Route::get("/cores/{filename}","EditorController@getCoreFile");
Route::put("/cores/{filename}","EditorController@saveCoreFile");
Route::delete("/cores/{filename}","EditorController@deleteCoreFile");

Route::post('/trio/{table}', 'EditorController@deleteAll');
Route::delete('/trio/{table}', 'EditorController@deleteAll');

Route::get('/assets/{asset}', function( Request $req, $asset ){
    if(!File::exists(base_path("vendor/starlight93/laravel-smart-api/resources/assets/$asset"))){
        return response()->json(['message'=>'File tidak ditemukan'], 404);
    }
    return File::get(base_path("vendor/starlight93/laravel-smart-api/resources/assets/$asset"));
});

Route::post('/connect', function(Request $req){
    if( config('editor.password') != @$req->password ){
        return response()->json([
            'message'=>'unauthenticated'
        ], 401);
    }

    if(!env('LOG_SERVER') || !env('LOG_PATH')){
        return response()->json([
            'message'=>'LOG_SERVER & LOG_PATH env kosong'
        ], 422);
    }

    return response()->json([
        "socket_server"=> env('LOG_SERVER'),
        "socket_room"=>env('LOG_PATH'),
        "socket_protocol"=>'!debugger,reggubed!',
    ]);
});