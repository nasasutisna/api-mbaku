<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EbookController extends Controller
{
    public $tbl_ebook = 'ebook';
    public $tbl_feedback = 'feedback';

    public function __construct()
    {
        $this->category = DB::table('category');
        $this->ebook = DB::table('ebook');
    }

    public function getEbookList(Request $request){
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $sortBy = $request->input('sortBy');
        $filter = $request->input('filter');
        $filterFromHome = $request->input('filterFromHome');

        $keyword = $request->input('keyword');
        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

        $query =  DB::table($this->tbl_ebook);
        $query->select('category.categoryTitle', 'ebook.*', 'library.libraryID', 'library.libraryName');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedBackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as totalRate');
        $query->leftjoin('category', 'category.categoryID', '=', 'ebook.categoryID');
        $query->leftjoin('library', 'library.libraryID', '=', 'ebook.libraryID');

        // filter
        if ($filter) {
            $categorySelected = $filter['categorySelected'];
            $librarySelected = $filter['librarySelected'];

            if ($categorySelected) {
                $categoryID = $categorySelected['categoryID'];
                $query = $query->where('ebook.categoryID', $categoryID);
            }

            if ($librarySelected) {
                $libraryID = $librarySelected['libraryID'];
                $query = $query->where('ebook.libraryID', $libraryID);
            }
        }

        if ($filterFromHome == 'popular') {
            $query = $query->orderBy('totalRate', 'desc');
        }

        if ($filterFromHome == 'ebooknew') {
            $query = $query->orderBy('ebookRelease', 'desc');
        }

        // searching
        if ($keyword != '' && $keyword != 'undefined') {
            $query = $query->where('ebook.ebookTitle', $keyword);
        }

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);

        $query = $query->get();

        $data = array(
            'data' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'total' => $total,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }

    public function searchTitle(Request $request)
    {
        $keyword = $request->keyword;
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

        $query = $this->ebook->selectRaw("DISTINCT(ebookTitle) as ebookTitle")
            ->where('ebookTitle', 'like', '%' . $keyword . '%');

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);
        $query = $query->get();

        $data = array(
            'data' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'total' => $total,
            'totalPage' => $totalPage,
        );

        return response()->json($data, 200);
    }
    // public function getEbookList(Request $request)
    // {
    //     $arrCategory = [];
    //     $pageIndex = $request->input('pageIndex');
    //     $pageSize = $request->input('pageSize');
    //     $sortBy = $request->input('sortBy');

    //     $filter_category = json_decode($request->input('category'), true);
    //     $keyword = $request->input('keyword');
    //     $skip = ($pageIndex == 0) ? $pageIndex : ($pageIndex * $pageSize);

    //     $query = $this->ebook;
    //     $query->select('category.categoryTitle as category', 'ebook.*');
    //     $query->selectRaw('COALESCE((SELECT SUM(feedback.feedbackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as feedback');
    //     $query->leftjoin('category', 'category.categoryID', '=', 'ebook.categoryID');

    //     $count_page = DB::table('ebook')->count();

    //     // sort by stok
    //     if ($sortBy != 'undefined' && $sortBy != '') {
    //         $sortBy = explode('|', $sortBy);
    //         $query = $query->orderBy($sortBy[0], $sortBy[1]);
    //     }

    //     // filter category
    //     if ($filter_category != '' && count($filter_category) > 0) {
    //         foreach ($filter_category as $key => $value) {
    //             $fil_category = explode('|', $value);
    //             $fil_category = $fil_category[0];
    //             $arrCategory[] = $fil_category;
    //         }
    //         $query = $query->whereIn('ebook.categoryID', $arrCategory);
    //         $count_page = count($query->get());
    //     }

    //     // searching
    //     if ($keyword != '' && $keyword != 'undefined') {
    //         $query = $query->where('ebook.ebookTitle', 'like', '%' . $keyword . '%');
    //         $query = $query->orWhere('ebook.ebookWriter', 'like', '%' . $keyword . '%');
    //         $count_page = count($query->get());
    //     }

    //     $query->skip($skip);
    //     $query->limit($pageSize);

    //     $query = $query->get();
    //     $query = json_decode(json_encode($query), true);

    //     $data = array(
    //         'data' => $query,
    //         'limit' => $pageSize + 0,
    //         'page' => $pageIndex + 1,
    //         'totalPage' => $count_page,
    //     );

    //     return response()->json($data);
    // }

    public function store(Request $request)
    {
        $msg = '';
        $ebookCoverName = '';
        $status = 200;
        $date = date('Ymdhis');
        $uuid = Str::uuid();

        // check action add or edit true if its edit
        $isUpdate = $request->input('isUpdate') ? $request->input('isUpdate') : false;;
        $libraryID = $request->input('libraryID');
        $ebookCode = $uuid . '-' . $date;

        $ebookID =  ($isUpdate ? $request->input('ebookID') : $ebookCode);
        $ebookTitle = $request->input('ebookTitle');
        $categoryID = $request->input('categoryID');
        $ebookWriter = $request->input('ebookWriter');
        $ebookDescription = $request->input('ebookDescription');
        $ebookDistributor = $request->input('ebookDistributor');
        $ebookRelease = $request->input('ebookRelease');
        $ebookPrice = $request->input('ebookPrice');
        $createdBy = $request->input('createdBy');
        $updatedBy = $request->input('updatedBy');

        $ebookCover = $request->file('ebookCover');
        if ($ebookCover) {
            $ebookCoverName = str_replace(' ', '_', $date . '_' . $ebookCover->getClientOriginalName());
            $ebookCover->storeAs('public/ebookcover/' . $libraryID, $ebookCoverName);
        }

        $content = array(
            'ebookID' => $ebookID,
            'ebookTitle' => $ebookTitle,
            'ebookWriter' => $ebookWriter,
            'ebookDescription' => $ebookDescription,
            'ebookRelease' => $ebookRelease,
            'ebookDistributor' => $ebookDistributor,
            'ebookCover' => $ebookCoverName,
            'ebookPrice' => $ebookPrice,
            'categoryID' => $categoryID,
            'libraryID' => $libraryID,
        );

        if ($isUpdate == 'true') {;
            $content['updatedBy'] = $updatedBy;
            $query = $this->ebook->where('ebookID', $ebookID)->update($content);
        } else {
            $content['createdBy'] = $createdBy;
            $query = $this->ebook->insert($content);
        }

        $msg = 'Data berhasil disimpan';

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function getDetailEbook($id)
    {
        $ebookID = $id;
        $feedBack = 0;

        $getRate = DB::table($this->tbl_feedback)
            ->where('feedbackID', $ebookID)
            ->sum('feedBackValue');

        if ($getRate) {
            $feedBack = $getRate;
        }

        $query = $this->ebook;
        $query->select('ebook.*', 'category.categoryTitle', 'library.libraryName', 'university.universityName' );
        $query->leftjoin('category', 'category.categoryID', '=', 'ebook.categoryID');
        $query->leftjoin('library', 'library.libraryID', '=', 'ebook.libraryID');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->where('ebookID', $ebookID);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);
        $data['feedBack'] = $feedBack;

        return response()->json($data);
    }
}
