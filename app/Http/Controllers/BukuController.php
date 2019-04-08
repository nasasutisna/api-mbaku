<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BukuController extends Controller
{
    //
    public function __construct()
    {
        //
        $this->buku = DB::table('buku');
        $this->category = DB::table('kategori');
    }

    public function getDetailBook(Request $request, $id)
    {
        $kode_buku = $id;

        $query = $this->buku;
        $query->select('kategori.judul_kategori as kategori', 'buku.*');
        $query->join('kategori','kategori.kode_kategori','=','buku.kode_kategori');
        $query->where('kode_buku',$kode_buku);

        $query = $query->first();

        return response()->json($query);
    }
}
