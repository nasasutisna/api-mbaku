<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MemberController extends Controller
{
    public $tbl_member = 'member';
    public $tbl_transaction_loan = 'transaction_loan';

    public function __construct()
    {
        $this->member_premium = DB::table('member_premium');
        $this->transaction_loan = DB::table('transaction_loan');
    }

    public function createMember(Request $request)
    {
        $msg = "";
        $results = array();
        $data = array();

        $memberID = $request->input('memberID');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $email = $request->input('email');
        $memberPhone = $request->input('memberPhone');
        $password = bcrypt($request->input('password'));

        $member = DB::table('member');
        $check = $member->where('memberID', '=', $memberID)->first();

        if ($check) {
            $msg = "NIM / NIK sudah terdaftar";
            $results = array(
                'msg' => $msg,
            );
            return response()->json($results, 500);
        } else {
            $msg = "Berhasil mendaftar!";
            $record = array(
                'memberID' => $memberID,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'memberPhone' => $memberPhone,
            );

            $save_member = $member->insert($record);

            if ($save_member) {
                $record_user = array(
                    'email' => $email,
                    'password' => $password,
                    'status' => 0,
                );
                $save_users = DB::table('users')->insert($record_user);
            }

            $results = array(
                'msg' => $msg,
                'data' => $save_member,
            );

            return response()->json($results);
        }
    }

    public function updateMember(Request $request)
    {
        $msg = "";
        $filename = "";
        $status = 200;
        $results = array();
        $data = array();

        $memberID = $request->input('memberID');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $email = $request->input('email');
        $memberAddress = $request->input('memberAddress');
        $status = $request->input('status');
        $memberPhone = $request->input('memberPhone');

        $photo = $request->file("photo");
        if ($photo) {
            $filename = str_replace(' ', '_', date('Ymdhis') . '_' . $photo->getClientOriginalName());
            $storePhoto = $photo->storeAs('public/profile/' . $memberID . '/', $filename);
        }

        // print_r($request->all());

        $record = array(
            'memberID' => $memberID,
            'memberFirstName' => $firstName,
            'memberLastName' => $lastName,
            'email' => $email,
            'memberPhone' => $memberPhone,
            'memberAddress' => $memberAddress,
            'memberPhoto' => $filename,
        );

        $member = DB::table($this->tbl_member)->where('memberID', $memberID)->update($record);
        $msg = "Berhasil disimpan!";

        $results = array(
            'msg' => $msg,
            'data' => $member,
        );

        return response()->json($results);
    }

    public function updatePhotoProfile(Request $request)
    {
        $filename = '';
        $memberID = $request->input('memberID');

        $photo = $request->file("photo");
        if ($photo) {
            $filename = str_replace(' ', '_', date('Ymdhis') . '_' . $photo->getClientOriginalName());
            $storePhoto = $photo->storeAs('public/profile/' . $memberID . '/', $filename);
        }

        $member = DB::table($this->tbl_member)->where('memberID', $memberID)->update(['memberPhoto' => $filename]);

        if ($member) {
            $msg = 'berhasil upload';
        } else {
            $msg = 'gagal upload';
        }

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data);
    }
    public function registerAccount(Request $request)
    {
        // define result
        $msg = '';
        $status = 200;
        $data = [];

        // define table
        $member = DB::table('member');
        $users = DB::table('users');

        // define request input
        $email = $request->input('email');
        $password = bcrypt($request->input('password'));

        // check email member
        $check_email = $member->where('email', '=', $email)->first();

        if ($check_email) {
            // check email users
            $check_email_user = $users->where('email', '=', $email)->first();
            if ($check_email_user) {
                $status = 500;
                $msg = 'Email sudah terdaftar';
            } else {
                $record = array(
                    'email' => $email,
                    'password' => $password,
                );

                // save users
                $save_user = $users->insert($record);
                $msg = 'Berhasil mendaftar';
            }
        } else {
            $status = 500;
            $msg = 'Email belum terdaftar, silahkan daftar sebagai member';
        }

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function getDetail($id)
    {
        $data = [];
        $member = DB::table($this->tbl_member);
        $query = $member->select('member.*', 'mp.memberPremiumSaldo as memberSaldo', 'mp.memberPhotoKTP as KTP', 'mp.memberApproval')
            ->Where('member.memberID', $id)
            ->leftJoin('member_premium as mp', 'mp.memberID', '=', 'member.memberID')
            ->first();

        return response()->json($query, 200);
    }

    public function delete($id)
    {
        $member = DB::table($this->tbl_member);
        $data = $member->where('memberSerialID', $id)->delete();
        return response()->json($data, 200);
    }

    public function getDatamember(Request $request)
    {
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ? $pageIndex : ($pageIndex * $pageSize);

        $query = DB::table('member');

        $count_page = $query->count();

        // searching
        if ($keyword != '' && $keyword != 'undefined') {
            $query = $query->where('firstName', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('memberID', 'like', '%' . $keyword . '%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);
        $query->orderBy('memberSerialID', 'desc');

        $query = $query->get();
        $query = json_decode(json_encode($query), true);

        $data = array(
            'data' => $query,
            'limit' => $pageSize + 0,
            'page' => $pageIndex + 1,
            'totalPage' => $count_page,
        );

        return response()->json($data);
    }

    public function userBanner($id)
    {
        $memberID = $id;
        $isMemberPremium = 0;

        $checkMemberPremium = $this->member_premium->where("memberID", $memberID)->where("memberApproval", 1)->first();

        if ($checkMemberPremium) {
            $isMemberPremium = 1;
        } else {
            $isMemberPremium = 0;
        }

        $query = $this->transaction_loan;
        $query->where("memberID", $memberID)
            ->where("transactionLoanStatus", 0)
            ->leftjoin('book', 'book.bookID', '=', 'transaction_loan.bookID');

        $query = $query->first();

        $loanTrx = json_decode(json_encode($query), true);

        $data = array(
            'isMemberPremium' => $isMemberPremium,
            'borrow' => $loanTrx,
        );

        return response()->json($data);

    }

    public function upgradeUserPremium(Request $request)
    {
        
        $msg = '';
        $photoKTP1 = '';
        $photoKTP2= '';
        $status = 200;
        $date = date('Ymdhis');

        $memberID = $request->input('memberID');
        $memberPhotoKTP1 = $request->file('memberPhotoKTP1');
        $memberPhotoKTP2 = $request->file('memberPhotoKTP2');
        $emergencyName  = $request->input('emergencyName');
        $emergencyNumber = $request->input('emergencyNumber');
        $emergencyRole = $request->input('emergencyRole');

        if ($memberPhotoKTP1 && $memberPhotoKTP2) {
            $photoKTP1 = str_replace(' ', '_', $date . '_' . $memberPhotoKTP1->getClientOriginalName());
            $memberPhotoKTP1->storeAs('public/memberPremium/' . $memberID, $photoKTP1);

            $photoKTP2 = str_replace(' ', '_', $date . '_' . $memberPhotoKTP2->getClientOriginalName());
            $memberPhotoKTP2->storeAs('public/memberPremium/' . $memberID, $photoKTP2);
        }

        $content = array(
            'memberID' => $memberID,
            'memberPhotoKTP1' => $photoKTP1,
            'memberPhotoKTP2' => $photoKTP2,
            'emergencyName' => $emergencyName,
            'emergencyNumber' => $emergencyNumber,
            'emergencyRole' => $emergencyRole,
            'memberPremiumSaldo' => 0,
            'memberApproval' => 1
        );

        $query = $this->member_premium->insert($content);

        if ($query){
            $member = DB::table('member');
            $member->select('member_Premium.*', 'member.*');
            $member->leftjoin('member_Premium', 'member_Premium.memberID', '=', 'member.memberID');
            $member->where("member.memberID", $memberID);
            $member = $member->get();

            foreach($member as $d){
                $data = [
                    'memberPremiumID' => $d->memberPremiumID,
                    'emergencyName' => $d->emergencyName,
                    'emergencyNumber' => $d->emergencyNumber,
                    'emergencyRole' => $d->emergencyRole,
                    'memberID' => $d->memberID,
                    'memberFirstName' => $d->memberFirstName,
                    'memberLastName' => $d->memberLastName,
                    'memberGender' => $d->memberGender,
                    'memberPhone' => $d->memberPhone,
                    'memberEmail' => $d->memberEmail,
                    'memberAddress' => $d->memberAddress
                ];
            
            }

            Mail::send('approval', $data, function($message) use ($memberID, $photoKTP1, $photoKTP2 ){
                $message->to('mbakuteam@gmail.com', 'Admin MBAKU')->subject('[MBAKU] Approval Upgrade Member Premium');
                $message->attach(storage_path('app/public/memberPremium/'.$memberID.'/'.$photoKTP1));
                $message->attach(storage_path('app/public/memberPremium/'.$memberID.'/'.$photoKTP2));
                
            });
            
            $msg = 'Request has been sent';
        }
        else {
            $msg = 'Request failed to sent';
            $status = 500;
        }
        
        $data = array(
            'msg' => $msg,
            'status' => $status
        );

        return response()->json($data);
    }

    public function memberApproved($memberPremiumID)
    {
        $query = $this->member_premium->where('memberPremiumID', $memberPremiumID)->update([
            'memberApproval' => 2
            ]);
        
        if($query){
            $status = 200;
            $msg = 'Request has approved';
            
        }
        else{
            $status = 500;
            $msg = 'approval is error';
        }

        return view('EmailVerified', ['status' => $status, 'msg' => $msg]);
    }

    public function memberRejected($memberPremiumID)
    {
        $query = $this->member_premium->where('memberPremiumID', $memberPremiumID)->update([
            'memberApproval' => 3
            ]);
        
        if($query){
            $status = 200;
            $msg = 'Request has rejected';
            
        }
        else{
            $status = 500;
            $msg = 'reject is error';
        }

        return view('EmailVerified', ['status' => $status, 'msg' => $msg]);
    }
}
