<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans;

class PaymentController extends Controller
{
    public $tbl_payment_ebook = 'payment_ebook';
    public $tbl_payment_loan = 'payment_loan';
    public $tbl_ebook_rentals = 'ebook_rentals';
    public $tbl_member_premium = 'member_premium';
    public $tbl_member_saldo_log = 'member_saldo_log';
    public $tbl_library_saldo_log = 'library_saldo_log';
    public $tbl_mbaku_saldo_log = 'mbaku_saldo_log';

    public function purchase(Request $request)
    {
        $full_name = $request->input('full_name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $ebookID = $request->input('ebookID');
        $ebookTitle = $request->input('ebookTitle');
        $ebookPrice = $request->input('ebookPrice');
        $paymentType = $request->input('paymentType');

        $transaction_details = [
            'order_id' => 'MBAKU-' . time(),
            'gross_amount' => $ebookPrice,
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
            'name' => $ebookTitle,
            'price' => $ebookPrice,
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
            $transaction_data['enabled_payments'] = array("bca_va", "permata_va", "bni_va", "echannel");
        }

        // print_r($transaction_data);exit();
        $result = Midtrans::getSnapTransaction($transaction_data);
        return response()->json($result, 200);
    }

    public function getOrderStatus($id)
    {
        $result = Midtrans::status($id);
        return response()->json($result, 200);
    }

    public function cancelOrder($order_id)
    {
        $result = Midtrans::cancel($order_id);
        return response()->json($result, 200);
    }

    public function checkExistsTransaction(Request $request)
    {
        $data = array(
            'message' => '',
        );

        $status = 200;

        $memberID = $request->input('memberID');
        $transaction_status = $request->input('transaction_status');

        $checkOrder = DB::table('payment')
            ->where('memberID', $memberID)
            ->where('transaction_status', 'pending')
            ->get();

        if (count($checkOrder) > 0) {
            $status = 401;
            $data['message'] = 'Anda masih mempunyai transaksi pembayaran yang belum diselesaikan';
        }

        return response()->json($data, $status);
    }

    public function orderBookPending(Request $request)
    {
        $data = array();

        $status = 200;

        $memberID = $request->input('memberID');
        $ebookID = $request->input('ebookID');
        $transaction_status = $request->input('transaction_status');

        $checkOrder = DB::table('payment')
            ->where('memberID', $memberID)
            ->where('ebookID', $ebookID)
            ->get();

        if (count($checkOrder) > 0) {
            $data['data'] = json_decode(json_encode($checkOrder), true);
        } else {
            $data['data'] = [];
        }

        return response()->json($data, $status);
    }

    public function savePaymentEbook(Request $request)
    {
        $content = array();
        $data = array();
        $status = 200;
        $uuid = Str::uuid();

        $memberID = $request->input('memberID');
        $ebookID = $request->input('ebookID');
        $libraryID = $request->input('libraryID') ? $request->input('libraryID') : 0;
        $orderID = $request->input('order_id');
        $paymentType = $request->input('payment_type');
        $amount = $request->input('gross_amount');
        $paymentStatus = $request->input('transaction_status');
        $paymentDateTime = $request->input('transaction_time') ? $request->input('transaction_time') : date('Y-m-d h:i:s');
        $paymentToken = $request->input('payment_token') ? $request->input('payment_token') : $uuid;
        $saldo = $request->input('saldo') ? $request->input('saldo') : 0;

        $content['libraryID'] = $libraryID;
        $content['memberID'] = $memberID;
        $content['ebookID'] = $ebookID;
        $content['orderID'] = $orderID;
        $content['paymentType'] = $paymentType;
        $content['amount'] = $amount;
        $content['paymentToken'] = $paymentToken;
        $content['paymentStatus'] = $paymentStatus;
        $content['paymentDateTime'] = $paymentDateTime;

        $save = DB::table($this->tbl_payment_ebook)->insert($content);

        if ($save) {
            $dateNow = date('Y-m-d');
            $expireDate = date('Y-m-d', strtotime($dateNow . ' + 14 days'));

            $content2['ebookID'] = $ebookID;
            $content2['memberID'] = $memberID;
            $content2['expireDate'] = $expireDate;

            $saveEbook = DB::table($this->tbl_ebook_rentals)->insert($content2);

            if ($saveEbook && $saldo > 0) {
                $currentSaldo = (int) $saldo - (int) $amount;
                $updateSaldo = DB::table($this->tbl_member_premium)->where('memberID', $memberID)->update(['memberPremiumSaldo' => $currentSaldo]);

                if ($updateSaldo) {
                    $this->saveLogSaldo($content);
                }
            }

            if ($saveEbook) {
                $data['message'] = 'success';
            } else {
                $status = 422;
                $data['message'] = 'failed';
            }
        } else {
            $status = 422;
            $data['message'] = 'failed';
        }

        return response()->json($data, $status);
    }

    public function updateStatusOrder(Request $request)
    {
        $content = array();
        $data = array();
        $status = 200;

        $memberID = $request->input('memberID');
        $transaction_token = $request->input('transaction_token');
        $transaction_status = $request->input('transaction_status');

        $content['transaction_status'] = $transaction_status;
        // $content['transaction_time'] = $request->input('transaction_time');

        $save = DB::table('payment')->where('transaction_token', $transaction_token)->update($content);
        $data['message'] = 'success';

        return response()->json($data, $status);
    }

    public function getOrderByAnggota($memberID)
    {
        $data = array();
        $status = 200;

        $order = DB::table('payment')
            ->where('memberID', $memberID)
            ->leftjoin('book', 'book.ebookID', '=', 'payment.ebookID')
            ->leftjoin('category', 'category.categoryID', '=', 'book.categoryID')
            ->orderBy('payment.serial_id', 'desc')
            ->get();

        if (count($order) > 0) {
            $data['data'] = json_decode(json_encode($order), true);
        } else {
            $data['data'] = [];
        }

        return response()->json($data, $status);
    }

    public function saveLogSaldo($data = array())
    {
        $content = array();
        if (count($data) > 0) {
            $memberID = $data['memberID'];
            $libraryID = $data['libraryID'];
            $amount = $data['amount'];

            $content['memberID'] = $memberID;
            $content['nominal'] = $amount;
            $content['saldoLogType'] = 'Kredit';
            $content['paymentType'] = 'Mbaku Wallet';

            $saveMemberLog = DB::table($this->tbl_member_saldo_log)->insert($content);

            if($saveMemberLog){
                unset($content['memberID']);
                $content['saldoLogType'] = 'Debit';
                $content['libraryID'] = $libraryID;
                $saveLibraryLog = DB::table($this->tbl_library_saldo_log)->insert($content);

                if($saveLibraryLog){
                    unset($content['libraryID']);
                    $content['nominal'] = $amount * 0.1;
                    $saveMbakuLog = DB::table($this->tbl_mbaku_saldo_log)->insert($content);
                }
            }
        }
    }
}
