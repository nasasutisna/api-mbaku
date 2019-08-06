<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;

class BookController extends Controller
{
    //
    public $tbl_book = 'book';
    public $tbl_feedback = 'feedback';

    public function __construct()
    {
        $this->category = DB::table('category');
        $this->book = DB::table('book');
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
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedbackValue) FROM feedback where feedback.ebookID = book.bookID),0) as feedback');
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

    public function getDetailBook(Request $request, $id)
    {
        $bookID = $id;
        $feedBack = 0;

        $getRate = DB::table($this->tbl_feedback)
            ->where('bookID', $bookID)
            ->sum('feedBackValue');

        if ($getRate) {
            $feedBack = $getRate;
        }

        $query = $this->book;
        $query->select('category.categoryTitle', 'book.*');
        $query->leftjoin('category', 'category.categoryID', '=', 'book.categoryID');
        $query->where('bookID', $bookID);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);
        $data['feedBack'] = $feedBack;

        return response()->json($data);
    }

    public function getBookByCategory(Request $request, $id)
    {
        $categoryID = $id;

        $query = $this->book;
        $query->where('categoryID', $categoryID);
        $query->limit(40);
        $query = $query->get();

        $arrPage = [];
        $arrTemp = [];
        $no = 0;
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

    public function getEbook(Request $request)
    {
        $filename = $request->input('filename');
        $file = Storage::disk('public')->path('ebook/' . $filename);

        return response()->download($file);
    }

    public function checkMyRate(Request $request)
    {
        $memberID = $request->input('memberID');
        $bookID = $request->input('bookID');
        $rate = 0;

        $query = DB::table($this->tbl_feedback)->where('memberID', '=', $memberID)->where('bookID', '=', $bookID)->first();

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
        $query = $this->book;
        $query->select('book.*', 'category.categoryTitle');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.rate) FROM feedback where feedback.ebookID = book.bookID),0) as feedback');
        $query->limit(10);
        $query->leftjoin('category', 'category.categoryID', '=', 'book.categoryID');
        $query = $query->orderBy('feedback', 'desc');

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
        $bookID = $request->input('bookID');
        $judul = $request->input('judul');
        $categoryID = $request->input('categoryID');
        $pengarang = $request->input('pengarang');
        $sinopsis = $request->input('sinopsis');
        $penerbit = $request->input('penerbit');
        $tahun_terbit = $request->input('tahun_terbit');
        $stok = $request->input('stok');
        $jumlah = $request->input('jumlah');
        $harga_ebook = $request->input('harga_ebook');

        $arrData = array(
            'bookID' => $bookID,
            'judul' => $judul,
            'categoryID' => $categoryID,
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
            $query = DB::table($this->tbl_book)->where('serial_id', $serial_id)->update($arrData);

        } else {
            $query = DB::table($this->tbl_book)->insert($arrData);
        }

            $msg = 'Data berhasil disimpan';
            $status = 200;

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function addfeedback(Request $request)
    {
        $memberID = $request->input('memberID');
        $bookID = $request->input('bookID');
        $feedBackValue = $request->input('feedBackValue');

        $check = DB::table($this->tbl_feedback)->where('memberID', '=', $memberID)->where('bookID', '=', $bookID)->first();

        $content = array(
            'memberID' => $memberID,
            'bookID' => $bookID,
            'rate' => $feedBackValue,
        );

        if ($check) {
            $query = DB::table($this->tbl_feedback)
                ->where('memberID', $memberID)
                ->where('bookID', $bookID)
                ->update(['rate' => $feedBackValue]);
        } else {
            $query = DB::table($this->tbl_feedback)->insert($content);
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
        $anggota = DB::table($this->tbl_book);
        $data = $anggota->where('serial_id',$id)->delete();
        return response()->json($data, 200);
    }
}
