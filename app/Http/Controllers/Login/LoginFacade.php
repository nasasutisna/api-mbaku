<?php

namespace App\Http\Controllers\Login;

use App\Http\Constants\ResponseConstants;
use App\Http\Utils\ResponseException;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use App\User;
use Illuminate\Support\Facades\Auth;

class LoginFacade
{

    public function __construct()
    { }

    public function doLogin(Request $request)
    {
        $data = [];
        $userLogin = [];
        $getToken = [];

        try
        {
            $checkUser = $this->doCheckUser($request->email);

            // check validation user
            if ($checkUser)
            {
                $verify = password_verify($request->password, $checkUser->password);
                // check valdation verify password
                if($verify){
                    if($checkUser->role == 0)
                    {
                        // get data member
                        $user = getMember($checkUser->email);
                    } else {
                        // get data staff
                        $user = DB::table('staff');
                        $user->select('staff.*', 'library.*');
                        $user->leftjoin('library', 'library.libraryID', '=',  'staff.libraryID');
                        $user->where('staff.staffEmail',$checkUser->email);
                        $user = $user->first();
                    }

                    //get user info
                    $userLogin = array(
                        'userInfo' => $user,
                        'userStatus' => $checkUser->role
                    );

                    $isLogin = $verify;

                    // generate token
                    $credentials = request(['email', 'password']);
                    if(Auth::attempt($credentials))
                    {
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

                    $data = array(
                        'user' => $userLogin,
                        'token' => $getToken,
                        'isLogin' => array(
                            'status' => $isLogin,
                        ),
                    ); 

                    return  $data;

                } else {
                    // validation invalid password
                    throw new ResponseException(ResponseConstants::LOGIN_INVALID_PASSWORD);
                }

            } else if ($this->doCheckEmailExistRegist($request->email)) {
                // validation email exist and need verify
                throw new ResponseException(ResponseConstants::REGISTRATION_NEED_VERIFY);
            } else {
                // validation user not found
                throw new ResponseException(ResponseConstants::LOGIN_USER_NOT_FOUND);
            }
            
        } catch (Exception $e) {
            throw new Exception($e);
        }
            
    }

    private function doCheckUser($email)
    {
        $isExistOnUser = DB::table('users')->where("email", '=', $email)->first();
        return $isExistOnUser;
    }

    private function doCheckEmailExistRegist($email)
    {
        $isExistOnRegist = DB::table('registration')->where("memberEmail", '=', $email)->where('expiryAt', '>', Carbon::now()->toDateTimeString())->first();
        return $isExistOnRegist;
    }

    private function getMember($email)
    {
        $member = DB::table('member')->where("memberEmail", '=', $email)->first();
        return $member;
    }
}
