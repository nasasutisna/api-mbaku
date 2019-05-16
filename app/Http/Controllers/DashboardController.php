<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //

    var $tbl_ratting = 'ratting';

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
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ?  $pageIndex : ($pageIndex  * $pageSize);

        $query = $this->buku;
        $query->select('kategori.judul_kategori as kategori', 'buku.*');
        $query->selectRaw('COALESCE((SELECT SUM(ratting.rate) FROM ratting where ratting.kode_buku = buku.kode_buku),0) as ratting');
        $query->leftjoin('kategori','kategori.kode_kategori','=','buku.kode_kategori');
        $query->leftjoin('ratting','ratting.kode_buku','=','buku.kode_buku');

        $count_page = DB::table('buku')->count();

        // sort by stok
        if($sortBy != 'undefined' && $sortBy != ''){
            $sortBy = explode('|',$sortBy);
            $query = $query->orderBy($sortBy[0],$sortBy[1]);
        }

        // filter category
        if ($filter_category != '' && count($filter_category) > 0) {
            foreach ($filter_category as $key => $value) {
                $fil_category = explode('|', $value);
                $fil_category = $fil_category[0];
                $arrCategory[] = $fil_category;
            }
           $query = $query->whereIn('buku.kode_kategori', $arrCategory);
           $count_page = count($query->get());
        }

        // searching
        if($keyword != '' && $keyword != 'undefined'){
            $query = $query->where('buku.judul', 'like', '%'.$keyword.'%');
            $query = $query->orWhere('buku.pengarang', 'like', '%'.$keyword.'%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);

        $query = $query->get();
        $query = json_decode(json_encode($query),true);

        // get ratting from table ratting
        // foreach ($query as $key => $value) {
        //     $ratting = 0;
        //     $getRate = DB::table($this->tbl_ratting)
        //     ->where('kode_buku',$value['kode_buku'])
        //     ->sum('rate');

        //     if($getRate){
        //         $ratting = $getRate;
        //     }

        //     $query[$key]['ratting'] = $ratting;
        // }

        // sort by ratting
        // if($sortBy != 'undefined' && $sortBy != ''){
        //     if($sortBy[0] == 'ratting'){

        //         $sort_col = array();
        //         foreach ($query as $key=> $row) {
        //             $sort_col[$key] = $row['ratting'];
        //         }

        //         if($sortBy[1] == 'asc'){
        //             array_multisort($sort_col, SORT_ASC,$query);
        //         }
        //         else{
        //             array_multisort($sort_col, SORT_DESC,$query);
        //         }
        //     }
        // }

        $data = array(
            'data' => $query,
            'limit' => $pageSize + 0,
            'page' => $pageIndex + 1,
            'totalPage' => $count_page
        );

        return response()->json($data);
    }

    public function array_sort_by_column($arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }

    public function getDataCategory()
    {
        $data = $this->category->orderBy('judul_kategori', 'asc')->get();
        return response()->json($data);
    }

    function array_sort($array, $on, $order='desc'){

        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case 'asc':
                    sort($sortable_array);
                    break;
                case 'desc':
                    rsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return array_values($new_array);
    }
}
