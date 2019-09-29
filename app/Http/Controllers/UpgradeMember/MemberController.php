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

    public function memberApprove($id)
    {
        $data = [];
        try {
            $approve = new MemberFacade();
            $approve->doApprove($id);

            $data = ResponseConstants::SUBMISSION_APPROVE_SUCCESS;
        } catch (ResponseException $th) {
            $data = $th->getResponse();
        } catch (Throwable $th) {
            $data = ResponseConstants::ERROR;
            $data['error_msg'] = $th->getMessage();
            $data['stactrace'] = $th->getTraceAsString();
        }

        return view('updateMember', $data);
    }

    public function memberReject($id)
    {
        $data = [];
        try {
            $reject = new MemberFacade();
            $reject->doReject($id);

            $data = ResponseConstants::SUBMISSION_REJECT_SUCCESS;
        } catch (ResponseException $th) {
            $data = $th->getResponse();
        } catch (Throwable $th) {
            $data = ResponseConstants::ERROR;
            $data['error_msg'] = $th->getMessage();
            $data['stactrace'] = $th->getTraceAsString();
        }

        return view('updateMember', $data);
    }
}
