<?php

namespace App\Http\Controllers;

use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Storage;

class BookController extends Controller
{
    public $tbl_feedback = 'feedback';

    public function __construct()
    {
        $this->category = DB::table('category');
        $this->book = DB::table('book');
    }

    public function getBookList(Request $request)
    {
        $arrCategory = [];
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $sortBy = $request->input('sortBy');
        $filterEbook = $request->input('filterEbook');

        $filter_category = $request->input('category') ? json_decode($request->input('category'), true) : [];
        $keyword = $request->input('keyword');

        $skip = ($page == 1) ? $page-1 : (($page-1) * $limit);


        $query = $this->book;
        $query->select('category.categoryTitle', 'book.*');
        $query->leftjoin('category', 'category.categoryID', '=', 'book.categoryID');

        // sort by stok
        if ($sortBy != 'undefined' && $sortBy != '') {
            $sortBy = explode('|', $sortBy);
            $query = $query->orderBy($sortBy[0], $sortBy[1]);
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

        if ($filterEbook) {
            $query = $query->where('ebook', '!=', '');
            $count_page = count($query->get());
        }

        // searching
        if ($keyword != '' && $keyword != 'undefined') {
            $query = $query->where('book.bookTitle', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('book.bookWriter', 'like', '%' . $keyword . '%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($limit);

        $temp = $query;
        $countRows = $temp->count();
        $totalPage = $countRows <= $limit ?  1 : ceil($countRows / $limit);

        $query = $query->get();

        $data = array(
            'data' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'total' => $countRows,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }

    public function getDetailBook( $id)
    {
        $bookID = $id;

        $query = $this->book->select('category.categoryTitle', 'book.*')
                ->leftjoin('category', 'category.categoryID', '=', 'book.categoryID')
                ->where('bookID', $bookID);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);

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
        $status = 200;
        $imagePath = '';
        $ebookName = '';
        $date = date('Ymdhis');
        $uuid = Str::uuid();

        $image = $request->file('path_image');
        if ($image) {
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
        $libraryID = $request->input('libraryID');
        $defaultBookID = $date . '-' . $uuid;

        $bookSerialID = $request->input('bookSerialID');
        $bookID = $request->input('bookID') ? $request->input('bookID') : $defaultBookID;
        $bookTitle = $request->input('bookTitle');
        $categoryID = $request->input('categoryID');
        $bookWriter = $request->input('bookWriter');
        $bookDescription = $request->input('bookDescription');
        $bookDistributor = $request->input('bookDistributor');
        $bookRelease = $request->input('bookRelease');
        $bookTotal = $request->input('bookTotal');
        $bookStock = $request->input('bookStock') || $bookTotal;
        $createdBy = $request->input('createdBy');
        $updatedBy = $request->input('updatedBy');

        $content = array(
            'bookID' => $bookID,
            'bookTitle' => $bookTitle,
            'categoryID' => $categoryID,
            'bookWriter' => $bookWriter,
            'bookDescription' => $bookDescription,
            'bookRelease' => $bookRelease,
            'bookDistributor' => $bookDistributor,
            'bookStock' => $bookStock,
            'bookTotal' => $bookTotal,
            'categoryID' => $categoryID,
            'libraryID' => $libraryID,
        );

        if ($imagePath) {
            $content['path_image'] = $imagePath;
        }

        if ($ebookName) {
            $content['ebook'] = $ebookName;
        }

        if ($isUpdate == 'true') {;
            $content['updatedBy'] = $updatedBy;
            $query = $this->book->where('bookSerialID', $bookSerialID)->update($content);
        } else {
            $content['createdBy'] = $createdBy;
            $query = $this->book->insert($content);
        }

        $msg = 'Data berhasil disimpan';

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

    public function delete($id)
    {
        $data = $this->book->where('bookSerialID', $id)->delete();
        return response()->json($data, 200);
    }

    public function searchTitle(Request $request)
    {
        $keyword = $request->keyword;
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $skip = ($page == 1) ? $page-1 : (($page-1) * $limit);

        $query = $this->book->selectRaw("DISTINCT(bookTitle) as bookTitle")
            ->where('bookTitle', 'like', '%'.$keyword.'%')
            ->skip($skip)
            ->limit($limit);

        $temp = $query;
        $countRows = $temp->count();
        $totalPage = $countRows <= $limit ?  1 : ceil($countRows / $limit);
        $query = $query->get();

        $data = array(
            'data' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'total' => $countRows,
            'totalPage' => $totalPage
        );

        return response()->json($data, 200);
    }

}
