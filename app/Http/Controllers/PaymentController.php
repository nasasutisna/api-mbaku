<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans;

class PaymentController extends Controller
{
    public function addTransaction(Request $request)
    {

    }

    public function purchase(Request $request)
    {
        $full_name = $request->input('full_name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $ebookID = $request->input('ebookID');
        $judul = $request->input('judul');
        $price = $request->input('price');

        $transaction_details = [
            'order_id' => 'MBAKU-' . time(),
            'gross_amount' => $price,
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
            'id' => $ebookID,
            'quantity' => 1,
            'name' => $judul,
            'price' => $price,
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

    public function saveOrder(Request $request)
    {
        $content = array();
        $data = array();
        $status = 200;

        $memberID = $request->input('memberID');
        $ebookID = $request->input('ebookID');
        $transaction_status = $request->input('transaction_status');

        $content['memberID'] = $memberID;
        $content['ebookID'] = $ebookID;
        // $content['transaction_id'] = $request->input('transaction_id');
        $content['orderID'] = $request->input('order_id');
        $content['paymentType'] = $request->input('payment_type');
        $content['grossAmount'] = $request->input('gross_amount');
        $content['paymentToken'] = $request->input('token');
        $content['paymentStatus'] = $transaction_status;
        $content['paymentDateTime'] = $request->input('transaction_time');

        $save = DB::table('payment')->insert($content);
        $data['message'] = 'success';

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
