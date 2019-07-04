<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;

class BukuController extends Controller
{
    //
    public $tbl_buku = 'buku';
    public $tbl_ratting = 'ratting';
    public function __construct()
    {
        //

        $this->category = DB::table('kategori');
        $this->buku = DB::table('buku');
    }

    public function getDetailBook(Request $request, $id)
    {
        $kode_buku = $id;
        $ratting = 0;

        $getRate = DB::table($this->tbl_ratting)
            ->where('kode_buku', $kode_buku)
            ->sum('rate');

        if ($getRate) {
            $ratting = $getRate;
        }

        $query = $this->buku;
        $query->select('kategori.judul_kategori as kategori', 'buku.*');
        $query->leftjoin('kategori', 'kategori.kode_kategori', '=', 'buku.kode_kategori');
        $query->where('kode_buku', $kode_buku);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);
        $data['ratting'] = $ratting;

        return response()->json($data);
    }

    public function getBookByCategory(Request $request, $id)
    {
        $kode_kategori = $id;

        $query = $this->buku;
        $query->where('kode_kategori', $kode_kategori);
        $query->limit(40);
        $query = $query->get();

        $arrPage = [];
        $arrTemp = [];
        $no = 0;
        $key = 0;

        // print_r(count($query));
        // exit;
        if ($query) {
            foreach ($query as $value) {
                if (count($arrTemp) > 0) {
                    if (count($arrTemp[$key]) < 5) {
                        $arrPage[$key][] = $value;
                        $arrTemp[$key][] = $value;
                    } else {
                        $arrTemp = [];
                        $key++;
                    }
                }

                if (count($arrTemp) == 0) {
                    $arrPage[$key][] = $value;
                    $arrTemp[$key][] = $value;
                }
            }
        }

        return response()->json($arrPage);
    }

    public function getEbook(Request $request)
    {
        $filename = $request->input('filename');
        // $check = Storage::exists(public_path().'/coverbook/php komplet.jpg');
        $file = Storage::disk('public')->path('ebook/' . $filename);

        return response()->download($file);
    }

    public function checkMyRate(Request $request)
    {
        $kode_anggota = $request->input('kode_anggota');
        $kode_buku = $request->input('kode_buku');
        $rate = 0;

        $query = DB::table($this->tbl_ratting)->where('kode_anggota', '=', $kode_anggota)->where('kode_buku', '=', $kode_buku)->first();

        if ($query) {
            $rate = $query->rate;
        }

        $data = array(
            'myRate' => $rate,
        );

        return response()->json($data);
    }

    public function getPopularBook()
    {
        $query = $this->buku;
        $query->select('buku.*', 'kategori.judul_kategori');
        $query->selectRaw('COALESCE((SELECT SUM(ratting.rate) FROM ratting where ratting.kode_buku = buku.kode_buku),0) as ratting');
        $query->limit(10);
        $query->leftjoin('kategori', 'kategori.kode_kategori', '=', 'buku.kode_kategori');
        $query = $query->orderBy('ratting', 'desc');

        $query = $query->get();

        $arrPage = [];
        $arrTemp = [];
        $key = 0;

        if ($query) {
            foreach ($query as $value) {
                if (count($arrTemp) > 0) {
                    if (count($arrTemp[$key]) < 5) {
                        $arrPage[$key][] = $value;
                        $arrTemp[$key][] = $value;
                    } else {
                        $arrTemp = [];
                        $key++;
                    }
                }

                if (count($arrTemp) == 0) {
                    $arrPage[$key][] = $value;
                    $arrTemp[$key][] = $value;
                }
            }
        }

        return response()->json($arrPage);
    }

    public function store(Request $request)
    {
        $msg = '';
        $status = '';
        $imagePath= '';
        $ebookName= '';

        $image = $request->file('path_image');
        if($image){
            $imageName = $image->getClientOriginalName();
            $imagePath = 'storage/app/public/coverbook/' . $imageName;
            $store = $image->storeAs('public/coverbook', $imageName);
        }

        $ebook = $request->file('ebook');
        if ($ebook) {
            $ebookName = $ebook->getClientOriginalName();
            $storeEbook = $ebook->storeAs('public/ebook', $ebookName);
        }

        // check action add or edit true if its edit
        $isUpdate = $request->input('isUpdate');

        $serial_id = $request->input('serial_id');
        $kode_buku = $request->input('kode_buku');
        $judul = $request->input('judul');
        $kode_kategori = $request->input('kode_kategori');
        $pengarang = $request->input('pengarang');
        $sinopsis = $request->input('sinopsis');
        $penerbit = $request->input('penerbit');
        $tahun_terbit = $request->input('tahun_terbit');
        $stok = $request->input('stok');
        $jumlah = $request->input('jumlah');
        $harga_ebook = $request->input('harga_ebook');

        $arrData = array(
            'kode_buku' => $kode_buku,
            'judul' => $judul,
            'kode_kategori' => $kode_kategori,
            'pengarang' => $pengarang,
            'sinopsis' => $sinopsis,
            'penerbit' => $penerbit,
            'tahun_terbit' => $tahun_terbit,
            'stok' => $stok,
            'harga_ebook' => $harga_ebook,
            'jumlah' => $jumlah
        );

        if($imagePath){
            $arrData['path_image'] = $imagePath;
        }

        if($ebookName){
            $arrData['ebook'] = $ebookName;
        }

        if ($isUpdate) {
            $query = DB::table($this->tbl_buku)->where('serial_id', $serial_id)->update($arrData);

        } else {
            $query = DB::table($this->tbl_buku)->insert($arrData);
        }

            $msg = 'Data berhasil disimpan';
            $status = 200;

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function addRatting(Request $request)
    {
        $kode_anggota = $request->input('kode_anggota');
        $kode_buku = $request->input('kode_buku');
        $ratting = $request->input('ratting');

        $check = DB::table($this->tbl_ratting)->where('kode_anggota', '=', $kode_anggota)->where('kode_buku', '=', $kode_buku)->first();

        $content = array(
            'kode_anggota' => $kode_anggota,
            'kode_buku' => $kode_buku,
            'rate' => $ratting,
        );

        if ($check) {
            $query = DB::table($this->tbl_ratting)
                ->where('kode_anggota', $kode_anggota)
                ->where('kode_buku', $kode_buku)
                ->update(['rate' => $ratting]);
        } else {
            $query = DB::table($this->tbl_ratting)->insert($content);
        }

        if ($query) {
            $msg = 'Data berhasil disimpan';
            $status = 200;
        } else {
            $status = 500;
            $msg = 'gagal';
        }

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function delete($id){
        $anggota = DB::table($this->tbl_buku);
        $data = $anggota->where('serial_id',$id)->delete();
        return response()->json($data, 200);
    }
}
