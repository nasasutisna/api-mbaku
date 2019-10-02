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

    public function doSendLinkReset(Request $request)
    {
        $email = $request->email;

        date_default_timezone_set('Asia/Jakarta');
        $date = Carbon::now();

        if ($this->doCheckEmailExistRegist($email)) {
            // check validation email exist and need verify
            throw new ResponseException(ResponseConstants::REGISTRATION_NEED_VERIFY);
        } else if ($this->doCheckUser($email) == null) {
            // check validation email not found
            throw new ResponseException(ResponseConstants::LOGIN_USER_NOT_FOUND);
        }
        else if ($this->doCheckEmailQueue($email) >=  $date) {
            // check validation email in email_queue
            throw new ResponseException(ResponseConstants::RESET_ALREADY_SENT);
        } 
        else if ($this->doCheckEmailSent($email) >=  $date ) {
            // check validation email has been sent
            throw new ResponseException(ResponseConstants::RESET_ALREADY_SENT);
        }
        else {
            try {
                DB::beginTransaction();

                // get param
                $emailTitle = DB::table('param')->select('paramValue')->where("paramKey", "email.reset.title")->first()->paramValue;
                $emailContent = DB::table('param')->select('paramValue')->where("paramKey", "email.reset.content")->first()->paramValue;

                //get id user
                $id = DB::table('users')->where('email', $email)->value('id');

                //url reset password
                $btnReset = url('resetpassword/'.base64_encode($email).'/'.base64_encode($id).'/'.base64_encode($date));

                $emailContent = str_replace('{{url}}', $btnReset, $emailContent);

                // do insert
                DB::table('email_queue')->insert([
                    'emailDest' => $email,
                    'emailTitle' => $emailTitle,
                    'emailContent' => $emailContent
                ]);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception($e);
            }
        }
    }

    public function doReset(Request $request)
    {
        $dateRequest = $request->date;
        date_default_timezone_set('Asia/Jakarta');
        //time expire link
        $expiryAt = (new DateTime($dateRequest))->modify(30 . ' minute');
        $currentTime = Carbon::now();

        // print_r($expiryAt); exit();
        if ($expiryAt < $currentTime) {
            // check validation request date request is expire
            throw new ResponseException(ResponseConstants::RESET_LINK_EXPIRED);
        } 
        else {
            try {
                DB::beginTransaction();

                //do update password
                DB::table('users')->where('email', $request->email)->update([
                    'password' => bcrypt($request->password),
                ]);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception($e);
            }
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

    private function doCheckEmailQueue($email)
    {
        $isOnEmailQueue = DB::table('email_queue')->where("emailDest", '=', $email)->orderBy('createdDt', 'Desc')->first();

        if($isOnEmailQueue != null)
        {
            $sentTime = (new DateTime($isOnEmailQueue->createdDt))->modify(5 . ' minute');
        } else{
            $sentTime = null;
        }

        return $sentTime;
    }

    private function doCheckEmailSent($email)
    {
        $isOnEmailSent = DB::table('email_sent')->where("emailDest", '=', $email)->orderBy('createdDt', 'Desc')->first();
        
        if($isOnEmailSent != null)
        {
            $sentTime = (new DateTime($isOnEmailSent->createdDt))->modify(5 . ' minute');
        } else{
            $sentTime = null;
        }

        return $sentTime;
    }
}
