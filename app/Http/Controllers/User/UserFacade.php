<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserFacade 
{
    
    public function getUserInfo(Request $request) {
        try {
            // get user data
            $user = $this->getUserInfoData($request->memberID);

            // update flag on table transaction_load
            $this->doUpdateShowTransFlag($request->memberID);

            // return data user
            return $user == null ? [] : $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    private function getUserInfoData($memberID) {
        return DB::table(DB::raw('(select a.*, b.createdDt transDt, b.isAlreadyAlert isAlreadyAlertTrans, c.memberPremiumSaldo from member a 
            left join (select * from transaction_loan order by createdDt desc limit 1) b on a.memberID=b.memberID
            left join member_premium c on a.memberID=c.memberID) x'))->where('memberID', '=', $memberID)->first();
    }

    private function doUpdateShowTransFlag($memberID) {
        DB::table('transaction_loan')->where('memberID', '=', $memberID)->update(['isAlreadyAlert' => true]);
    }

}
