<?php

namespace App\Http\Controllers\Register;

use App\Http\Constants\ResponseConstants;
use App\Http\Controllers\Controller;
use App\Http\Utils\ResponseException;
use Illuminate\Http\Request;
use Throwable;

class RegisterController extends Controller
{
    public function __construct()
    { }

    public function registerUser(Request $request)
    {
        $data = [];
        try {
            $register = new RegisterFacade();
            $register->doRegister($request);

            $data = ResponseConstants::REGISTRATION_SUCCESS;
        } catch (ResponseException $th) {
            $data = $th->getResponse();
        } catch (Throwable $th) {
            $data = ResponseConstants::ERROR;
            $data['error_msg'] = $th->getMessage();
            $data['stactrace'] = $th->getTraceAsString();
        }

        return response()->json($data, $data['status']);
    }

    public function verifyUser(Request $request)
    {
        $data = [];
        $memberID = $request['id'];
        $signature = $request['signature'];
        try {
            $facade = new RegisterFacade();
            $facade->doVerify($memberID, $signature);

            $data = ResponseConstants::VERIFY_SUCCESS;
        } catch (ResponseException $th) {
            $data = $th->getResponse();
        } catch (Throwable $th) {
            $data = ResponseConstants::ERROR;
            $data["error_msg"] = $th->getMessage();
            $data["stacktrace"] = $th->getTraceAsString();
        }

        return view('EmailVerified', $data);
    }
}
