<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use App\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class LoginController extends Controller
{
    public function __construct()
    {
        $this->users = DB::table('users');
        $this->member = DB::table('member');
        $this->staff = DB::table('staff');
    }

    public function processLogin(Request $request){

        $data = [];
        $msg = '';
        $isLogin = false;
        $status = 200;
        $token   = '';
        $userLogin = [];
        $getToken = [];

        $email = $request->input('email');
        $password = $request->input('password');

        $checkUser = $this->users->where('email','=',$email)->first();

        if($checkUser){
            $pass = $checkUser->password;
            $verify = password_verify($password, $pass);

            if($verify){
                if($checkUser->role == 0){
                    $user = $this->member->where('memberEmail','=',$checkUser->email)->first();
                }
                else{
                    $user = $this->staff->where('staffEmail','=',$checkUser->email)->first();
                }

                $msg = 'success';

                $userLogin = array(
                    'userInfo' => [],
                    'userStatus' => $checkUser->role
                );

                if($user){
                    $userLogin = array(
                        'userInfo' => $user,
                        'userStatus' => $checkUser->role
                    );
                }

                $isLogin = $verify;

                //start generate token
                $credentials = request(['email', 'password']);
                if(Auth::attempt($credentials)){
                    $user = $request->user();
                    $tokenResult = $user->createToken('Personal Access Token');
                    $token = $tokenResult->token;

                    $getToken = array(
                        'access_token' => $tokenResult->accessToken,
                        'token_type' => 'Bearer',
                        'expires_at' => Carbon::parse(
                            $tokenResult->token->expires_at
                        )->toDateTimeString()
                    );
                }
                //end generate token

            }
            else{
                $isLogin = false;
                $status = 401;
                $msg = 'Password salah';
            }
        }
        else{
            $isLogin = false;
            $status = 401;
            $msg = 'Email tidak terdaftar';
        }



        $data = array(
            'msg' => $msg,
            'user' => $userLogin,
            'token' => $getToken,
            'isLogin' => array(
                'status' => $isLogin,
            ),
        );


        return response()->json($data,$status);
    }

    public function generateToken(){
        $token = 'MBAKU-'.hash('sha256', Str::random(32));
        return $token;
    }

    public function invalidToken(Request $request){

        $data = [];
        $isToken = false;
        $status = 401;
        $msg = 'Unauthorization';

        $data = array(
            'status' => $status,
            'msg' => $msg,
            'isToken' => $isToken
        );

        return response()->json($data);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
