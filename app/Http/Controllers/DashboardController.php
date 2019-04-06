<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //

    public function __construct()
    {
        //
        $this->buku = DB::table('buku');
        $this->category = DB::table('kategori');
    }

    public function index(Request $request)
    {

        // $data = $this->buku->join('kategori','buku.kode_kategori = kategori.kode_kategori')->orderBy('ratting','desc')->paginate(5);
        // return response()->json($data);
    }

    public function getDataBook(Request $request)
    {
        $req = $request->all();
        $query = $this->buku;
        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');

        $arrCategory = [];

        if ($filter_category != '' && count($filter_category) > 0) {
            foreach ($filter_category as $key => $value) {
                $fil_category = explode('|', $value);
                $fil_category = $fil_category[0];
                $arrCategory[] = $fil_category;
            }
           $query = $query->whereIn('buku.kode_kategori', $arrCategory);
        }

        if($keyword != ''){
            $query = $query->where('buku.judul', 'like', '%'.$keyword.'%');
            $query = $query->orWhere('buku.pengarang', 'like', '%'.$keyword.'%');
        }

        $query->select('kategori.judul_kategori as kategori', 'buku.*');
        $query->join('kategori','kategori.kode_kategori','=','buku.kode_kategori');

        $query = $query->orderBy('ratting', 'desc')->paginate(5);

        return response()->json($query);
    }

    public function getDataCategory()
    {
        $data = $this->category->orderBy('judul_kategori', 'desc')->get();
        return response()->json($data);
    }
}
