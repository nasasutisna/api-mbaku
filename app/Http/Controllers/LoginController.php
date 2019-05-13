<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
class LoginController extends Controller
{
    //
    public function processLogin(Request $request){

        $data = [];
        $msg = '';
        $isLogin = false;
        $status = 200;
        $token   = '';
        $userLogin = [];

        $email = $request->input('email');
        $password = $request->input('password');

        $users = DB::table('users');
        $anggota = DB::table('anggota');

        $query = $users;
        $check_email = $query->where('email','=',$email)->first();

        if($check_email){
            $pass = $check_email->password;
            $verify = password_verify($password, $pass);

            if($verify){
                $anggota = $anggota->where('email','=',$email)->first();
                $msg = 'success';

                $userLogin = array(
                    'userInfo' => [],
                    'userStatus' => $check_email->status
                );

                if($anggota){
                    $userLogin = array(
                        'userInfo' => $anggota,
                        'userStatus' => $check_email->status
                    );
                }

                $isLogin = $verify;
                $token = $this->generateToken();
            }
            else{
                $isLogin = false;
                $status = 500;
                $msg = 'Password salah';
            }
        }
        else{
            $isLogin = false;
            $status = 500;
            $msg = 'Email tidak terdaftar';
        }

        $data = array(
            'msg' => $msg,
            'user' => $userLogin,
            'isLogin' => array(
                'status' => $isLogin,
                'auth' => $token
            ),
        );

        return response()->json($data,$status);
    }

    public function generateToken(){
        $var = 'MBAKU-'.Str::random(32);
        return $var;
    }
}
