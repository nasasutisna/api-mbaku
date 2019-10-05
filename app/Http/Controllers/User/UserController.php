<?php

namespace App\Http\Controllers\User;

use App\Http\Constants\ResponseConstants;
use App\Http\Controllers\Controller;
use App\Http\Utils\ResponseException;
use Illuminate\Http\Request;
use Throwable;

class UserController extends Controller
{
    public function __construct()
    {

    }

    public function getUserInfo(Request $request) {
        $data = [];
        try {
            $user = new UserFacade();
            $userData = $user->getUserInfo($request);

            $data = ResponseConstants::SUCCESS;
            $data['data'] = $userData;
        } catch (ResponseException $th) {
            $data = $th->getResponse();
        } catch (Throwable $th) {
            $data = ResponseConstants::ERROR;
            $data['error_msg'] = $th->getMessage();
            $data['stactrace'] = $th->getTraceAsString();
        }

        return response()->json($data, $data['status']);   
    }
    
}
