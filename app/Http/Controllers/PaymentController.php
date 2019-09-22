<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans;

class PaymentController extends Controller
{
    var $tbl_payment_ebook = 'payment_ebook';
    var $tbl_payment_loan = 'payment_loan';
    var $tbl_ebook_rentals = 'ebook_rentals';

    public function purchase(Request $request)
    {
        $full_name = $request->input('full_name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $ebookID = $request->input('ebookID');
        $ebookTitle = $request->input('ebookTitle');
        $ebookPrice = $request->input('ebookPrice');

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

        $memberID = $request->input('memberID');
        $ebookID = $request->input('ebookID');
        $libraryID = $request->input('libraryID');

        $content['libraryID'] = $libraryID;
        $content['memberID'] = $memberID;
        $content['ebookID'] = $ebookID;
        $content['orderID'] = $request->input('order_id');
        $content['paymentType'] = $request->input('payment_type');
        $content['amount'] = $request->input('gross_amount');
        $content['paymentToken'] = $request->input('payment_token');
        $content['paymentStatus'] = $request->input('transaction_status');
        $content['paymentDateTime'] = $request->input('transaction_time');

        $save = DB::table($this->tbl_payment_ebook)->insert($content);

        if($save){
            $dateNow = date('Y-m-d');
            $expireDate = date('Y-m-d', strtotime($dateNow. ' + 14 days'));

            $content2['ebookID'] = $ebookID;
            $content2['memberID'] = $memberID;
            $content2['expireDate'] = $expireDate;

            $saveEbook = DB::table($this->tbl_ebook_rentals)->insert($content2);

            if($saveEbook){
                $data['message'] = 'success';
            }
            else{
                $status = 422;
                $data['message'] = 'failed';
            }
        }
        else{
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
}
