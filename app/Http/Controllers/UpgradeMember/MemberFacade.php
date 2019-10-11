<?php

namespace App\Http\Controllers\UpgradeMember;

use App\Http\Constants\ResponseConstants;
use App\Http\Utils\ResponseException;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemberFacade
{

    public function __construct()
    { }

    public function doUpgrade(Request $request)
    {
        try {
            //get path image1 
            $image1 = 'app/public/memberPremium/' . $request->memberID . '/' . $request->memberPhotoKTP1;

            //get path image2
            $image2 = 'app/public/memberPremium/' . $request->memberID . '/' . $request->memberPhotoKTP2;

            DB::beginTransaction();

            // insert into table member_premium
            $this->doInsertMemberPremium($request);

            // insert data into email_queue table
            $this->doInsertEmailQueue($request, $image1, $image2);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    public function doApprove($id)
    {
        $memberPremiumID = $id;
        $memberID = DB::table('member_premium')->where("memberPremiumID", $memberPremiumID)->value('memberID');

        try {

            DB::beginTransaction();

            // update table member_premium
            $this->doUpdateMemberPremium($memberPremiumID, 1);

            // update table member
            $this->doUpdateMember($memberID, 1);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    public function doReject($id)
    {
        $memberPremiumID = $id;
        $memberID = DB::table('member_premium')->where("memberPremiumID", $memberPremiumID)->value('memberID');
        $dataMember = DB::table('member')->where("memberID", $memberID)->first();
        try {
            DB::beginTransaction();

            // update table member_premium
            $this->doUpdateMemberPremium($memberPremiumID, 2);

            // update table member
            $this->doUpdateMember($memberID, 0);

            // do send email notification
            $this->doSendInfoRejected($dataMember);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    private function doInsertEmailQueue($emailQueue, $image1, $image2)
    {
        // get param
        $emailRecipient = DB::table('param')->select('paramValue')->where("paramKey", "email.recipient.approval")->first()->paramValue;
        $emailTitle = DB::table('param')->select('paramValue')->where("paramKey", "email.approval.title")->first()->paramValue;
        $emailContent = DB::table('param')->select('paramValue')->where("paramKey", "email.approval.content")->first()->paramValue;

        //get data member
        $dataMember = DB::table('member')->where('memberID', $emailQueue->memberID)->first();
        $dataPremium = DB::table('member_premium')->where('memberID', $emailQueue->memberID)->where('memberApproval', 0)->first();

        //url approval
        $btnApprove = url('api/v1/member/approved/' . $dataPremium->memberPremiumID);
        $btnReject = url('api/v1/member/rejected/' . $dataPremium->memberPremiumID);

        $emailContent = str_replace('{{id}}', $emailQueue->memberID, $emailContent);
        $emailContent = str_replace('{{name}}', $dataMember->memberFirstName . ' ' . $dataMember->memberLastName, $emailContent);
        $emailContent = str_replace('{{gender}}', $dataMember->memberGender, $emailContent);
        $emailContent = str_replace('{{phone}}', $dataMember->memberPhone, $emailContent);
        $emailContent = str_replace('{{email}}', $dataMember->memberEmail, $emailContent);
        $emailContent = str_replace('{{address}}', $dataMember->memberAddress, $emailContent);
        $emailContent = str_replace('{{emergencyName}}', $emailQueue->emergencyName, $emailContent);
        $emailContent = str_replace('{{emergencyNumber}}', $emailQueue->emergencyNumber, $emailContent);
        $emailContent = str_replace('{{emergencyRole}}', $emailQueue->emergencyRole, $emailContent);
        $emailContent = str_replace('{{btnApprove}}', $btnApprove, $emailContent);
        $emailContent = str_replace('{{btnReject}}', $btnReject, $emailContent);

        // do insert
        return DB::table('email_queue')->insert([
            'emailDest' => $emailRecipient,
            'emailTitle' => $emailTitle,
            'emailContent' => $emailContent,
            'attachment1' => $image1,
            'attachment2' =>  $image2,
        ]);
    }

    private function doInsertMemberPremium($user)
    {
        DB::table('member_premium')->insert([
            'memberID' => $user->memberID,
            'memberPhotoKTP1' => $user->memberPhotoKTP1,
            'memberPhotoKTP2' => $user->memberPhotoKTP2,
            'emergencyName' => $user->emergencyName,
            'emergencyNumber' => $user->emergencyNumber,
            'emergencyRole' => $user->emergencyRole,
            'memberPremiumSaldo' => 0,
            'memberApproval' => 0,
        ]);
    }

    private function doUpdateMemberPremium($memberPremiumID, $approval)
    {
        return DB::table('member_premium')->where('memberPremiumID', $memberPremiumID)->update([
            'memberApproval' => $approval
        ]);
    }

    private function doUpdateMember($memberID, $role)
    {
        DB::table('member')->where('memberID', $memberID)->update([
            'memberRole' => $role
        ]);
    }

    private function getMember($memberID)
    {
        $dataMember = DB::table('member')->where("memberID", $memberID)->first();
        return $dataMember;
    }

    private function doSendInfoRejected($dataMember)
    {
        // get param
        $emailTitle = DB::table('param')->select('paramValue')->where("paramKey", "email.rejected.title")->first()->paramValue;
        $emailContent = DB::table('param')->select('paramValue')->where("paramKey", "email.rejected.content")->first()->paramValue;

        $emailContent = str_replace('{{name}}', $dataMember->memberFirstName . ' ' . $dataMember->memberLastName, $emailContent);

        // do insert
        return DB::table('email_queue')->insert([
            'emailDest' => $dataMember->memberEmail,
            'emailTitle' => $emailTitle,
            'emailContent' => $emailContent
        ]);
    }
}
