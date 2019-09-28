<?php

namespace App\Http\Controllers\UpgradeMember;

use App\Http\Constants\ResponseConstants;
use App\Http\Controllers\Controller;
use App\Http\Utils\ResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberController extends Controller
{
    public function __construct()
    { }

    public function upgradeUser(Request $request)
    {
        $data = [];
        try {
            $upgrade = new MemberFacade();
            $upgrade->doUpgrade($request);

            $data = ResponseConstants::SUBMISSION_SUCCESS;
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
