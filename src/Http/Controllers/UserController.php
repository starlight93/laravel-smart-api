<?php

namespace Starlight93\LaravelSmartApi\Http\Controllers;

use Carbon\Carbon;
use Starlight93\LaravelSmartApi\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;
use Validator;

class UserController extends Controller
{
    public function login(Request $request,$email_verified=false)
    {
        $validator = Validator::make( ($credentials=$request->all()), [
            'password' => 'required|string'
        ]);
        
        if ($validator->fails()) return response()->json([
            'message' => 'unauthenticated',
            'errors' => $validator->errors()
        ], 401);
        
        try{
            if( !($token = auth()->attempt( $credentials )) ) throw new \Exception();
            $email_verified = env('EMAIL_VERIFIED', false);
            $user = auth()->user();
            if( ($userActiveLogic = config('api.user_active_when')) ){
                $userActiveLogicArr = explode(":", $userActiveLogic );
                $column = $userActiveLogicArr[0];
                if( !$user->$column ) return response()->json(['message'=>'inactive user'],401);
                if(count( $userActiveLogicArr) == 2){
                    $value = strtolower(trim($userActiveLogicArr[1]));
                    if( strtolower(trim($user->$column)) != $value ) return response()->json(['message'=>'inactive user'],401);
                }
            }
        }catch(\Exception $err){
            return response()->json(['message'=>'unauthenticated'],401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in_mins' => config('jwt.ttl'),
            'data'=> $user
        ]);
    }

    public function logout(Request $request)
    {
        auth()->logout(true);;
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        $currentUser = auth()->user();
        // $expiration = Carbon::createFromTimestamp(auth()->payload()->get('exp'))->format('i');
        // $currentUser->expires_in_hours = $expiration;
        if( env("API_RESPONSE_FINALIZER") ){
            list($className, $method)= explode(".", env("RESPONSE_FINALIZER"));
            $class = Api::getCore( $className );
            $currentUser = $class->$method( $currentUser->toArray(), 'user' );
        }
        return $currentUser;
    }
    
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(),422);

        $currentUser = auth()->user();
        
        if ( Hash::check($request->current_password, $currentUser->password) ) {
            $currentUser->update([
                'password' => Hash::make( $request->new_password )
            ]);                
        }else{
            return response()->json(['message'=>'Mismatch Old Password!'], 401);
        }

        return [ 'message' => 'Successfully updated password!' ];
    }

// =================================== END

    public function register(Request $request,$local=false)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:default_users',
            'password' => 'required|string|confirmed'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' =>Hash::make($request->password),
            'remember_token'=>Str::random(60)
        ]);
        
        return $local?true:response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    public function verify($token)
    {
        $user = User::where('remember_token', $token)->first();
        if($user){
            $user->update([
                "email_verified_at"=>Carbon::now()
            ]);
            $template= "Your account($user->email) has been verified successfully!";
            return view("defaults.email",compact('template'));
        }else{
            $template= "Sorry your token is invalid!";
            return view("defaults.email",compact('template'));
        }
    }
    
    public function unlockScreen(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'password' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        
        $user = User::find(Auth::user()->id);
        $password = $request->password;

        if (Hash::check( base64_decode($request->password) , $user->password)) {
            return [ 'message'=>'unlocked successfully' ];
        }else{
            return response()->json(['message'=>'password salah'], 401);
        }
    }

    public function ResetPasswordLink( Request $request ){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'callback' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        $user = User::where( @$request->column ?? 'email', $request->email )->first();
        if( !$user ){
            return response()->json(['message'=>'user tidak ditemukan'], 401);
        }
        $token = random_str_cache(25, 600, $request->email, [1,2,3,4,5,6,7,8,9,0,'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z']);

        $res = SendEmail($request->email, env('APP_NAME').': Link Reset Password', 
            "Hai $user->name, Click link $request->callback?token=$token untuk reset password anda. <br/>"
            ."<i>Link ini berlaku hanya 10 menit sejak email ini dikirimkan<br/>"
            ."Abaikan jika anda tidak merasa ingin melakukan reset password</i>"
        );

        return response()->json([
            'message'=>"Link reset password telah dikirim ke $request->email berlaku(expired) 10 menit"
        ]);
    }
    
    public function ResetPasswordTokenVerify( Request $request, $token ){
        $account = get_random_str_cache( $token, $isPull=false );
        if( !$account ){
            return response()->json([
                'message'=>"Maaf token tidak dikenali atau mungkin telah expired"
            ], 401);
        }
        return response()->json([
            'verified' => true,
            'email' => $account,
            'url_change_password_post' => url("reset-password")
        ]);
    }
    
    public function ResetPassword( Request $request ){
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }
        
        $account = get_random_str_cache( $request->token, $isPull=false );
        if( !$account ){
            return response()->json([
                'message'=>"Maaf token tidak dikenali atau mungkin telah expired"
            ], 401);
        }

        $user = User::where( @$request->column ?? 'email', $account )->first();
        if( !$user ){
            return response()->json(['message'=>'user tidak ditemukan'], 401);
        }
        
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message'=>"Password telah berhasil diupdate, silahkan login"
        ]);
    }
}
