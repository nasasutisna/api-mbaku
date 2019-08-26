<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;


class LoginController extends Controller
{
    public function __construct()
    {
        $this->users = DB::table('users');
        $this->member = DB::table('member');
    }

    public function processLogin(Request $request){

        $data = [];
        $msg = '';
        $isLogin = false;
        $status = 200;
        $token   = '';
        $userLogin = [];

        $email = $request->input('email');
        $password = $request->input('password');

        $checkUser = $this->users->where('email','=',$email)->first();

        if($checkUser){
            $pass = $checkUser->password;
            $verify = password_verify($password, $pass);

            if($verify){
                $member = $this->member->where('memberEmail','=',$checkUser->email)->first();
                $msg = 'success';

                $userLogin = array(
                    'userInfo' => [],
                    'userStatus' => $checkUser->role
                );

                if($member){
                    $userLogin = array(
                        'userInfo' => $member,
                        'userStatus' => $checkUser->role
                    );
                }

                $isLogin = $verify;
                
                //start generate token
                $http = new Client;
                $response = $http->post('http://localhost:8000/oauth/token', [
                    'form_params' => [
                        'grant_type' => 'password',
                        'client_id' => '2',
                        'client_secret' => 'HT7xGudvaALL0unLTFN6x10QAKvjLVJYBIBBv3jX',
                        'username' => $request->email,
                        'password' => $request->password,
                        'scope' => '',
                    ],
                ]);

                $token = json_decode((string) $response->getBody(), true);

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
            'isLogin' => array(
                'status' => $isLogin,
                'token' => $token
            ),
        );

        return response()->json($data,$status,$token);
    }

    public function generateToken(){
        $token = 'MBAKU-'.hash('sha256', Str::random(32));
        return $token;
    }

}
