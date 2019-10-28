<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Midtrans;

class MemberController extends Controller
{
    public $tbl_member = 'member';
    public $tbl_member_saldo_log = 'member_saldo_log';
    public $tbl_member_premium = 'member_premium';
    public $tbl_transaction_loan = 'transaction_loan';

    public function __construct()
    {
        $this->member_premium = DB::table('member_premium');
        $this->transaction_loan = DB::table('transaction_loan');
    }

    public function updateMember(Request $request)
    {
        $msg = "";
        $filename = "";
        $status = 200;
        $results = array();
        $data = [];

        if (!empty($request->input('memberID')))
            $data['memberID'] = $request->input('memberID');
        if (!empty($request->input('firstName')))
            $data['memberFirstName'] = $request->input('firstName');
        if (!empty($request->input('lastName')))
            $data['memberLastName'] = $request->input('lastName');
        if (!empty($request->input('address')))
            $data['memberAddress'] = $request->input('address');
        if (!empty($request->input('phone')))
            $data['memberPhone'] = $request->input('phone');
        if (!empty($request->input('email')))
            $data['memberEmail'] = $request->input('email');
        if (!empty($request->input('gender')))
            $data['memberGender'] = $request->input('gender');

        $photo = $request->file("photo");
        if ($photo) {
            $filename = date('Ymdhis') . '.' . $photo->extension();
            $storePhoto = $photo->storeAs('public/profile/' . $data['memberID']  . '/', $filename);
            if ($storePhoto) $data['memberPhoto'] = $filename;
        }

        $member = DB::table($this->tbl_member)->where('memberID', $data['memberID'])->update($data);
        $msg = "Berhasil disimpan!";

        $results = array(
            'msg' => $msg,
            'data' => $member,
        );

        return response()->json($results);
    }

    public function getDetail($id)
    {
        $data = [];
        $member = DB::table($this->tbl_member);
        $query = $member->select('member.*', 'mp.memberPremiumSaldo as memberSaldo', 'mp.memberPhotoKTP1 as KTP1', 'mp.memberPhotoKTP1 as KTP1', 'mp.memberApproval')
            ->Where('member.memberID', $id)
            ->leftJoin('member_premium as mp', 'mp.memberID', '=', 'member.memberID')
            ->first();

        return response()->json($query, 200);
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

    public function uploadPhotoKTP(Request $request)
    {
        $memberID = $request->input('memberID');
        $memberPhotoKTP = $request->file('photo');
        $uuid = Str::uuid();

        $date = date('Ymdhis');
        $fileName = $memberID . '_' . $uuid . '.' . $memberPhotoKTP->extension();
        $memberPhotoKTP->storeAs('public/memberPremium/' . $memberID, $fileName);

        $data = array(
            'filename' => $fileName,
        );

        return response()->json($data, 200);
    }

    public function checkMemberStatus(Request $request)
    {

        $memberID = $request->input('memberID');
        $data = array();
        $data['memberRole'] = 0;
        $data['memberSaldo'] = 0;
        $data['submission'] = null;
        $memberRole = 0;

        $member = DB::table($this->tbl_member)
            ->where('memberID', $memberID)
            ->first();

        if ($member) {
            $memberRole = $member->memberRole;
        }

        if ($memberRole == 0) {
            $memberPremium = DB::table($this->tbl_member_premium)
                ->where('memberID', $memberID)
                ->first();

            if ($memberPremium) {
                $memberApproval = $memberPremium->memberApproval;

                if ($memberApproval == 0) {
                    $data['submission'] = 'pending';
                } else if ($memberApproval == 1) {
                    $data['memberRole'] = 1;
                    $data['submission'] = 'approved';
                } else {
                    $data['submission'] = 'reject';
                }
            }
        } else {
            $data['memberRole'] = 1;
            $data['submission'] = 'approved';
        }

        if ($data['memberRole'] == 1) {
            $query = DB::table($this->tbl_member_premium)->where('memberID', $memberID)->first();
            if ($query) {
                $data['memberSaldo'] = $query->memberPremiumSaldo ? (int) $query->memberPremiumSaldo : 0;
            }
        }

        return response()->json($data, 200);
    }

    public function topUpSaldo(Request $request)
    {

        $full_name = $request->input('full_name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $nominal = $request->input('nominal');
        $paymentType = $request->input('paymentType');

        $transaction_details = [
            'order_id' => 'MBAKU-' . time(),
            'gross_amount' => $nominal,
        ];

        $customer_details = [
            'first_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
        ];

        $custom_expiry = [
            'start_time' => date("Y-m-d H:i:s O", time()),
            'unit' => 'day',
            'duration' => 1,
        ];

        $item_details = [
            'id' => date('Ymdhis'),
            'quantity' => 1,
            'name' => 'TOP UP SALDO MBAKU',
            'price' => $nominal,
        ];

        $transaction_data = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
            'expiry' => $custom_expiry,
        ];

        if ($paymentType == 'gopay') {
            $transaction_data['enabled_payments'] = array("gopay");
        } else {
            $transaction_data['enabled_payments'] = array("bca_va", "permata_va", "bni_va", "echannel", "other_va");
        }

        $result = Midtrans::getSnapTransaction($transaction_data);
        return response()->json($result, 200);
    }

    public function saveSaldo(Request $request)
    {
        $content = array();
        $data = array();
        $status = 200;
        $msg = '';

        $memberID = $request->input('memberID');
        $nominal = $request->input('nominal');
        $type = $request->input('type');
        $paymentType = $request->input('paymentType');

        $content['memberID'] = $memberID;
        $content['nominal'] = $nominal;
        $content['saldoLogType'] = $type;
        $content['paymentType'] = $paymentType;

        $query = DB::table($this->tbl_member_saldo_log)->insert($content);

        if ($query) {
            $getCurrentSaldo = DB::table($this->tbl_member_premium)
                ->select('memberPremiumSaldo')
                ->where('memberID', $memberID)
                ->first();

            $currentSaldo = $getCurrentSaldo->memberPremiumSaldo;

            if ($type == 'topup') {
                $lastSaldo = (int) $currentSaldo + (int) $nominal;
            } else {
                $lastSaldo = (int) $currentSaldo - (int) $nominal;
            }

            $updateSaldo = DB::table($this->tbl_member_premium)
                ->where('memberID', $memberID)
                ->update(['memberPremiumSaldo' => $lastSaldo]);

            if ($updateSaldo) {
                $msg = 'berhasil';
            } else {
                $msg = 'gagal';
                $status = 422;
            }
        } else {
            $status = 422;
            $msg = 'gagal';
        }

        $data['msg'] = $msg;

        return response()->json($data, $status);
    }

    public function getStatusPayment(Request $request)
    {
        $id = $request->input('id');
        $result = Midtrans::status($id);
        return response()->json($result, 200);
    }
}
