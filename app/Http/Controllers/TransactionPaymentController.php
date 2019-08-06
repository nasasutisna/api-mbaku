<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans;

class TransactionPaymentController extends Controller
{

    public function __construct()
    {

    }

    public function getListByAnggota(Request $request)
    {
        $kode_anggota = $request->input('kode_anggota');
        $query = DB::table('peminjaman')
            ->join('buku', 'buku.kode_buku', '=', 'peminjaman.kode_buku')
            ->where('kode_anggota', $kode_anggota)
            ->where('status', '0')
            ->get();

        return response()->json($query, 200);
    }

    public function store(Request $request)
    {
        $tanggal_pengembalian = $request->input("tanggal_pengembalian");
        $tanggal_pengembalian = date("Y-m-d H:i:s", strtotime($tanggal_pengembalian));

        $transaction = $request->input("transaction");
        $kode_anggota = $request->input("kode_anggota");
        $bookList = $request->input("bookList");

        $tempData = [];
        if ($transaction == 'pengembalian') {
            // transaksi pengembalian
            if (count($bookList) > 0) {
                $arrKodePeminjaman = [];
                $no = 0;
                foreach ($bookList as $key => $value) {
                    $arrKodePeminjaman[$no++] = $value['kode_peminjaman'];
                    $this->updateStokBook('pengembalian', $value['kode_buku'], $value['jumlah_pinjam']);
                }
                $save = DB::table('peminjaman')->whereIn('kode_peminjaman', $arrKodePeminjaman)->update(["status" => 1]);
            }
        } else {
            // transaksi peminjaman
            if (count($bookList) > 0) {
                foreach ($bookList as $key => $value) {
                    $tempData[$key]['kode_anggota'] = $kode_anggota;
                    $tempData[$key]['kode_buku'] = $value['kode_buku'];
                    $tempData[$key]['tanggal_kembali'] = $tanggal_pengembalian;
                    $tempData[$key]['jumlah_pinjam'] = $value['qty'];
                    $this->updateStokBook('peminjaman', $value['kode_buku'], $value['qty']);
                }

                $save = DB::table('peminjaman')->insert($tempData);
            }
            return response()->json($save);
        }

    }

    public function updateStokBook($transaction, $kode_buku, $qty)
    {
        $getStok = DB::table('buku')->select('stok')->where('kode_buku', $kode_buku)->first();
        if ($transaction == 'peminjaman') {
            $stok = $getStok->stok - $qty;
        } else {
            $stok = $getStok->stok + $qty;
        }

        $query = DB::table('buku')->where('kode_buku', $kode_buku)->update(['stok' => $stok]);
        return response()->json($query, 200);
    }

    public function getDataTransaction(Request $request)
    {
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ? $pageIndex : ($pageIndex * $pageSize);

        $query = DB::table('peminjaman');
        $query->select('peminjaman.*', 'peminjaman.status as status_pinjam', 'buku.judul', 'buku.kode_buku', 'anggota.kode_anggota', 'anggota.nama_lengkap');
        $query->leftjoin('buku', 'buku.kode_buku', '=', 'peminjaman.kode_buku');
        $query->leftjoin('anggota', 'anggota.kode_anggota', '=', 'peminjaman.kode_anggota');
        $query->leftjoin('kategori', 'kategori.kode_kategori', '=', 'buku.kode_kategori');

        $count_page = $query->count();

        // searching
        if ($keyword != '' && $keyword != 'undefined') {
            $query = $query->where('anggota.nama_lengkap', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('anggota.kode_anggota', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('buku.kode_buku', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('buku.judul', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('buku.pengarang', 'like', '%' . $keyword . '%');
            $count_page = count($query->get());
        }

        if ($request->input('status') == '0') {
            $query = $query->where('peminjaman.status', '0');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);
        $query->orderBy('kode_peminjaman', 'desc');

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

    ##################################### TRANSAKSI MIDTRANS ########################################################

    public function addTransaction(Request $request)
    {

    }

    public function purchase(Request $request)
    {
        $full_name = $request->input('full_name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $kode_buku = $request->input('kode_buku');
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
            'id' => $kode_buku,
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

        $kode_anggota = $request->input('kode_anggota');
        $transaction_status = $request->input('transaction_status');

        $checkOrder = DB::table('transaction_order')
            ->where('kode_anggota', $kode_anggota)
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

        $kode_anggota = $request->input('kode_anggota');
        $kode_buku = $request->input('kode_buku');
        $transaction_status = $request->input('transaction_status');

        $checkOrder = DB::table('transaction_order')
            ->where('kode_anggota', $kode_anggota)
            ->where('kode_buku', $kode_buku)
        //    ->where('transaction_status','pending')
            ->get();

        if (count($checkOrder) > 0) {
            $data['data'] = json_decode(json_encode($checkOrder), true);
            //    $data['pending'] = 1; // pending
        } else {
            $data['data'] = [];
            // $data['pending'] = 0;
        }

        return response()->json($data, $status);
    }

    public function saveOrder(Request $request)
    {
        $content = array();
        $data = array();
        $status = 200;

        $kode_anggota = $request->input('kode_anggota');
        $kode_buku = $request->input('kode_buku');
        $transaction_status = $request->input('transaction_status');

        $content['kode_anggota'] = $kode_anggota;
        $content['kode_buku'] = $kode_buku;
        $content['transaction_id'] = $request->input('transaction_id');
        $content['order_id'] = $request->input('order_id');
        $content['payment_type'] = $request->input('payment_type');
        $content['gross_amount'] = $request->input('gross_amount');
        $content['transaction_token'] = $request->input('token');
        $content['transaction_status'] = $transaction_status;
        $content['transaction_time'] = $request->input('transaction_time');

        $save = DB::table('transaction_order')->insert($content);
        $data['message'] = 'success';

        return response()->json($data, $status);
    }

    public function updateStatusOrder(Request $request)
    {
        $content = array();
        $data = array();
        $status = 200;

        $kode_anggota = $request->input('kode_anggota');
        $transaction_token = $request->input('transaction_token');
        $transaction_status = $request->input('transaction_status');

        $content['transaction_status'] = $transaction_status;
        // $content['transaction_time'] = $request->input('transaction_time');

        $save = DB::table('transaction_order')->where('transaction_token', $transaction_token)->update($content);
        $data['message'] = 'success';

        return response()->json($data, $status);
    }

    public function getOrderByAnggota($kode_anggota)
    {
        $data = array();
        $status = 200;

        $order = DB::table('transaction_order')
            ->where('kode_anggota', $kode_anggota)
            ->leftjoin('buku', 'buku.kode_buku', '=', 'transaction_order.kode_buku')
            ->leftjoin('kategori', 'kategori.kode_kategori', '=', 'buku.kode_kategori')
            ->orderBy('transaction_order.serial_id', 'desc')
            ->get();

        //    print_r(' empty tes : '.!empty($order));
        //    exit();
        if (count($order) > 0) {
            $data['data'] = json_decode(json_encode($order), true);
        } else {
            $data['data'] = [];
        }

        return response()->json($data, $status);
    }

}
