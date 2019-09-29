<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    public function updateProfile(Request $request)
    {
        $filename = '';
        $status = 200;
        $content = array();

        $memberID = $request->input('memberID');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $phone = $request->input('phone');

        $content['memberFirstName'] = $firstName;
        $content['memberLastName'] = $lastName;
        $content['memberPhone'] = $phone;

        $photo = $request->file("file");
        if ($photo) {
            $filename = $photo->getClientOriginalName();
            $photo->storeAs('public/profile/' . $memberID . '/', $filename);
            $content['memberPhoto'] = $filename;
        }

        $member = DB::table($this->tbl_member)->where('memberID', $memberID)->update($content);

        if ($member) {
            $msg = 'berhasil upload';
        } else {
            $msg = 'gagal upload';
            $status = 422;
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
        $query = $member->select('member.*', 'mp.memberPremiumSaldo as memberSaldo', 'mp.memberPhotoKTP1 as KTP1', 'mp.memberPhotoKTP1 as KTP1', 'mp.memberApproval')
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
        $memberPhotoKTP = $request->file('file');

        $date = date('Ymdhis');
        $fileName = str_replace(' ', '_', $date . '_' . $memberPhotoKTP->getClientOriginalName());
        $memberPhotoKTP->storeAs('public/memberPremium/' . $memberID, $fileName);

        $data = array(
            'filename' => $fileName,
        );

        return response()->json($data, 200);
    }

    public function upgradeUserPremium(Request $request)
    {

        $msg = '';
        $photoKTP1 = '';
        $photoKTP2 = '';
        $status = 200;
        $date = date('Ymdhis');

        $memberID = $request->input('memberID');
        $emergencyName = $request->input('emergencyName');
        $emergencyNumber = $request->input('emergencyNumber');
        $emergencyRole = $request->input('emergencyRole');
        $image1 = $request->input('memberPhotoKTP1');
        $image2 = $request->input('memberPhotoKTP2');

        $content = array(
            'memberID' => $memberID,
            'memberPhotoKTP1' => $image1,
            'memberPhotoKTP2' => $image2,
            'emergencyName' => $emergencyName,
            'emergencyNumber' => $emergencyNumber,
            'emergencyRole' => $emergencyRole,
            'memberPremiumSaldo' => 0,
            'memberApproval' => 0,
        );

        DB::beginTransaction();

        try {
            $query = $this->member_premium->insert($content);

            if ($query) {
                $member = DB::table('member');
                $member->select('member_premium.*', 'member.*');
                $member->leftjoin('member_premium', 'member_premium.memberID', '=', 'member.memberID');
                $member->where("member.memberID", $memberID);
                $member = $member->get();

                foreach ($member as $d) {
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
                        'memberAddress' => $d->memberAddress,
                        'image1' => $image1,
                    ];

                }

                Mail::send('approval', $data, function ($message) use ($memberID, $image1, $image2) {
                    $message->from('donotreply@mbaku.online', 'Admin MBAKU');
                    $message->to('mbakuteam@gmail.com', 'Admin MBAKU')->subject('[MBAKU] Approval Upgrade Member Premium');
                    $message->attach(storage_path('app/public/memberPremium/' . $memberID . '/' . $image1));
                    $message->attach(storage_path('app/public/memberPremium/' . $memberID . '/' . $image2));

                });

            }
            DB::commit(); // all good

            $msg = 'Pengajuan berhasil dikirim';

        } catch (\Exception $e) {
            DB::rollback();

            $msg = 'Pengajuan gagal dikirim';
            $status = 500;
        }

        $data = array(
            'msg' => $msg,
            'status' => $status,
        );

        return response()->json($data, $status);
    }

    public function memberApproved($memberPremiumID)
    {
        $query = $this->member_premium->where('memberPremiumID', $memberPremiumID)->update([
            'memberApproval' => 1,
        ]);

        if ($query) {
            $member = $this->member_premium->where('memberPremiumID', $memberPremiumID)->select('memberID')->first();
            $memberID = $member->memberID;

            $updateMember = DB::table($this->tbl_member)->where('memberID', $memberID)->update([
                'memberRole' => 1,
            ]);

            // print_r($updateMember); exit();
            if ($updateMember) {
                $status = 200;
                $msg = 'Pengajuan berhasil disetujui';
            } else {
                $status = 422;
                $msg = 'Update member gagal';
            }
        } else {
            $status = 500;
            $msg = 'Persetujuan gagal';
        }

        return view('EmailVerified', ['status' => $status, 'msg' => $msg]);
    }

    public function memberRejected($memberPremiumID)
    {
        $query = $this->member_premium->where('memberPremiumID', $memberPremiumID)->update([
            'memberApproval' => 2,
        ]);

        if ($query) {
            $status = 200;
            $msg = 'Pengajuan berhasil ditolak';

        } else {
            $status = 500;
            $msg = 'pengajuan gagal';
        }

        return view('EmailVerified', ['status' => $status, 'msg' => $msg]);
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
            'duration' => 2,
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

    public function downloadImage($filename, $memberID)
    {
        $path = 'profile/' . $memberID . '/' . $filename;
        $file = Storage::disk('public')->path($path);

        return response()->download($file);
    }
}
