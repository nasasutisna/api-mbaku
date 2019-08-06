<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
    var $tbl_feedback = 'feedback';

    public function __construct()
    {
        //
        $this->book = DB::table('book');
        $this->category = DB::table('category');
    }

    public function index(Request $request)
    {

        // $data = $this->book->join('category','book.categoryID = category.categoryID')->orderBy('feedback','desc')->paginate(5);
        // return response()->json($data);
    }

    public function getBookList(Request $request)
    {
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');
        $filterEbook = $request->input('filterEbook');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ?  $pageIndex : ($pageIndex  * $pageSize);

        $query = $this->book;
        $query->select('category.categoryTitle as category', 'book.*');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedbackValue) FROM feedback where feedback.bookID = book.bookID),0) as feedback');
        $query->leftjoin('category','category.categoryID','=','book.categoryID');

        $count_page = DB::table('book')->count();

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
           $query = $query->whereIn('book.categoryID', $arrCategory);
           $count_page = count($query->get());
        }


        if($filterEbook) {
            $query = $query->where('ebook','!=','');
            $count_page = count($query->get());
        }

        // searching
        if($keyword != '' && $keyword != 'undefined'){
            $query = $query->where('book.bookTitle', 'like', '%'.$keyword.'%');
            $query = $query->orWhere('book.bookWriter', 'like', '%'.$keyword.'%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);

        $query = $query->get();
        $query = json_decode(json_encode($query),true);

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
        $data = $this->category->orderBy('categoryTitle', 'asc')->get();
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

    ##################################### DASHBOARD ADMIN #######################################################
    function dashboardAdmin(){
        $data = array();
        $book = DB::table('book')->selectRaw("sum(jumlah) as jumlah, sum(stok) as stok")->first();
        $member = DB::table('member')->count();

        $month = date('m');
        $totalLoan =  DB::table('transaction_loan')->selectRaw("count(transactionLoanID) as total")->whereMonth('tanggal_pinjam',$month)->first();

        $data['book'] = $book;
        $data['member'] = $member;
        $data['totalLoan'] = $totalLoan;

        return response()->json($data, 200);

    }

}
