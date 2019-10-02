<?php

namespace App\Http\Controllers\Login;

use App\Http\Constants\ResponseConstants;
use App\Http\Controllers\Controller;
use App\Http\Utils\ResponseException;
use Illuminate\Http\Request;
use Throwable;

class LoginController extends Controller
{
    public function __construct()
    { }

    public function processLogin(Request $request)
    {
        $getLogin['user'] = [];
        $getLogin['token'] = [];
        $getLogin['isLogin'] = array('status' => false);
        $status = [];

        try {
            $login = new LoginFacade();
            
            $getLogin = $login->doLogin($request);

            $status = ResponseConstants::LOGIN_SUCCESS;
        } catch (ResponseException $th) {
            $status = $th->getResponse();
        } catch (Throwable $th) {
            $status = ResponseConstants::ERROR;
            $status['error_msg'] = $th->getMessage();
            $status['stactrace'] = $th->getTraceAsString();
        }

        $data = array(
            'msg' => $status['msg'],
            'user' => $getLogin['user'],
            'token' => $getLogin['token'],
            'isLogin' => $getLogin['isLogin'],
        );

        return response()->json($data, $status['status']);
    }

    public function invalidToken()
    {
        $data = [];
        $isToken = false;
        $status = 401;
        $msg = 'Tidak terautentikasi';

        $data = array(
            'msg' => $msg,
            'isToken' => $isToken
        );

        return response()->json($data, $status);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
