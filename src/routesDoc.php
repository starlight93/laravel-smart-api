<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Jfcherng\Diff\Factory\RendererFactory;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

Route::get('/', function(){
    return response()->json([
        "Application"=> env('APP_NAME')." v ".(app()->version()),
        "PHP Version" => "PHP: ".phpversion().", OS: ".PHP_OS_FAMILY.", User: ".get_current_user(),
        "Database" => DB::connection()->getDatabaseName()." (".DB::connection()->getDriverName().")",
        "Debugging" => env("APP_DEBUG"),
        "DB Schema" => url("/docs/schema"),
        "Scheduler" => url("/docs/scheduler"),
        "List Menu" => url("/docs/menu"),
        "Activities" => url("/docs/activities"),
        "Logs" => url("/docs/logs"),
        "Data Uploader" => url("/docs/uploader"),
        "API Doc" => url("/docs/api"),
        "API Request" => url("/docs/api-request")
    ]);
});

Route::get('/schema', 'EditorController@getGeneratedSchema');
Route::get('/activities', function(){
    return Ed::getDeveloperActivities( html: true );
});

Route::get('/activities/{id}', function( Request $req, $id ){
    $activities = Cache::get( "developer_activities" );
    foreach($activities as $act){
        if( @$act['id']==$id && @$act['diff'] ){
            $diff = $act['diff'];
            $css = url("laradev/assets/diff-table");
            $result = RendererFactory::make('SideBySide', [
                'showHeader'=>false
            ])->renderArray(json_decode( $diff, true ));
            return "<link rel='stylesheet' href='$css'><p style='font-weight:semibold;'>$id ~ {$act['time']} ~ {$act['action']} ~ {$act['file']}</p>".$result;
        }
    }
    return "detail was not-found";
});

/* ==================================================== EDITOR ================================= */
Route::get('/uploader', function(Request $req){
    return view("editor::unauthorized")->with('data',[
        'url'=>url("docs/uploader")
    ]);
});

Route::post('/uploader', function(Request $req){
    if( @$req->password!=config("editor.password") ){
        return view("editor::unauthorized")->with('data',[
            'url'=>url("/docs/uploader"),
            'salah'=>true
        ]);
    }else{
        $schema = Cache::get('generated-models-schema');
        return view("editor::uploader", compact('schema'));
    }
});

/*=========================================================== Menu ==================================*/
Route::get('/menu', function(Request $req){
    return view("editor::unauthorized")->with('data',[
        'url'=>url("docs/menu")
    ]);
});

Route::post('/menu', function(Request $req){
    if( (@$req->password??@$req->header('authorization'))!=config("editor.password") ){
        return view("editor::unauthorized")->with('data',[
            'url'=>url("/docs/menu"),
            'salah'=>true
        ]);
    }else{
        if($req->header('authorization')){
            $data = $req->all();
            $data['created_at'] = \Carbon::now();
            $createdId = DB::table('default_menu')->insertGetId($data);
            return response()->json( ['message'=>'created successfully', 'id'=>$createdId]);
        }else{
            $schedules = DB::table('default_menu')->orderByRaw('project, modul, submodul, sequence, menu')->get();
            return view("editor::menu", compact('schedules'));
        }
    }
});

Route::delete('/menu/{id}', function(Request $req, $id){
    if( @$req->header('authorization')!=config("editor.password") ) abort(401, 'Unauthorized');
    DB::table('default_menu')->where('id', $id)->delete();
    return response()->json( ['message'=>'deleted successfully']);
});

Route::put('/menu/{id}', function( Request $req, $id ){
    if( @$req->header('authorization')!=config("editor.password") ) abort(401, 'Unauthorized');
    $data = $req->all();
    $data['updated_at'] = \Carbon::now();
    DB::table('default_menu')->where('id', $id)->update($data);
    return response()->json( ['message'=>'updated successfully']);
});
/*=========================================================== Menu ==================================*/
Route::get('/scheduler', function(Request $req){
    return view("editor::unauthorized")->with('data',[
        'url'=>url("docs/scheduler")
    ]);
});

Route::post('/scheduler', function(Request $req){
    if( (@$req->password??@$req->header('authorization'))!=config("editor.password") ){
        return view("editor::unauthorized")->with('data',[
            'url'=>url("/docs/scheduler"),
            'salah'=>true
        ]);
    }else{
        if($req->header('authorization')){
            $data = $req->all();
            $data['created_at'] = \Carbon::now();
            $createdId = DB::table('default_schedules')->insertGetId($data);
            return response()->json( ['message'=>'created successfully', 'id'=>$createdId]);
        }else{
            $schedules = DB::table('default_schedules')->get();
            return view("editor::scheduler", compact('schedules'));
        }
    }
});

Route::delete('/scheduler/{id}', function(Request $req, $id){
    if( @$req->header('authorization')!=config("editor.password") ) abort(401, 'Unauthorized');
    DB::table('default_schedules')->where('id', $id)->delete();
    return response()->json( ['message'=>'deleted successfully']);
});

Route::put('/scheduler/{id}', function( Request $req, $id ){
    if( @$req->header('authorization')!=config("editor.password") ) abort(401, 'Unauthorized');
    $data = $req->all();
    $data['updated_at'] = \Carbon::now();
    DB::table('default_schedules')->where('id', $id)->update($data);
    return response()->json( ['message'=>'updated successfully']);
});

Route::get('/api', function(Request $req){
    $schema = json_decode(json_encode(Cache::get('generated-models-schema')));
    return view("editor::api-doc", compact('schema'));
});

Route::get('/api-request', function(Request $req){
    return view("editor::api-request");
});
/* ==================================================== LOG ================================= */
Route::get('/logs', function(Request $req){
    if( @$req->has('l') || @$req->has('clean') || @$req->has('del') || @$req->has('dl') || @$req->has('delall') ){
        return (new LogViewerController)->index();
    }
    return view("editor::unauthorized")->with('data',[
        'url'=>$req->fullUrl()
    ]);
});

Route::post('/logs', function(Request $req){
    if( @$req->password!=config("editor.password") ){
        return view("editor::unauthorized")->with('data',[
            'url'=>url("docs/logs"),
            'salah'=>true
        ]);
    }else{
        return (new LogViewerController)->index();
    }
});



/* ==================================================== LOG ================================= */
