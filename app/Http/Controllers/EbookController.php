<?php

namespace App\Http\Controllers;

use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Storage;

class EbookController extends Controller
{
    public $tbl_ebook = 'ebook';
    public $tbl_ebook_rentals = 'ebook_rentals';
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
            $query = $query->where('ebook.ebookTitle','like','%'.$keyword.'%');
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
    
    public function store(Request $request)
    {
        $msg = '';
        $ebookCoverName = '';
        $ebookFileName = '';
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

        $ebookFile = $request->file('ebookFile');
        if ($ebookFile) {
            $ebookFileName = str_replace(' ', '_', $date . '_' . $ebookFile->getClientOriginalName());
            $ebookFile->storeAs('public/ebook/' . $libraryID, $ebookFileName);
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
            'ebookFile' => $ebookFileName
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
            ->where('ebookID', $ebookID)
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


    public function getEbook(Request $request)
    {
        $filename = $request->input('filename');
        $libraryID =  $request->input('libraryID')+0;

        $path = 'ebook/'.$libraryID.'/'.$filename;
        $file = Storage::disk('public')->path($path);

        return response()->download($file);
    }

    public function checkAccessRead(Request $request)
    {
        $data = array();

        $memberID = $request->input('memberID');
        $ebookID =  $request->input('ebookID');
        $date = date('Y-m-d');

        $query =  DB::table($this->tbl_ebook_rentals)
                ->where('memberID',$memberID)
                ->where('ebookID',$ebookID)
                ->where('expireDate','>=',$date)
                ->first();

        if($query){
            $data['allowedRead'] = true;
        }
        else{
            $data['allowedRead'] = false;
        }

        return response()->json($data, 200);
    }

    public function addFeedBack(Request $request)
    {
        $memberID = $request->input('memberID');
        $ebookID = $request->input('ebookID');
        $feedBackValue = $request->input('feedBackValue');

        $check = DB::table($this->tbl_feedback)->where('memberID', '=', $memberID)->where('ebookID', '=', $ebookID)->first();

        $content = array(
            'memberID' => $memberID,
            'ebookID' => $ebookID,
            'feedBackValue' => $feedBackValue,
        );

        if ($check) {
            $query = DB::table($this->tbl_feedback)
                ->where('memberID', $memberID)
                ->where('ebookID', $ebookID)
                ->update(['feedBackValue' => $feedBackValue]);
        } else {

            $query = DB::table($this->tbl_feedback)->insert($content);
        }

        if ($query) {
            $msg = 'Data berhasil disimpan';
            $status = 200;
        } else {
            $status = 422;
            $msg = 'gagal';
        }

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function checkMyFeedBack(Request $request)
    {
        $memberID = $request->input('memberID');
        $ebookID = $request->input('ebookID');
        $feed = 0;

        $query = DB::table($this->tbl_feedback)->where('memberID', '=', $memberID)->where('ebookID', '=', $ebookID)->first();

        if ($query) {
            $feed = $query->feedBackValue;
        }

        $data = array(
            'feed' => $feed,
        );

        return response()->json($data);
    }

    public function deleteEbook($id)
    {
        $ebookID = $id;
        $msg = '';
        $status = 200;

        $chckLibrary = $this->ebook->where('ebookID',$ebookID)->first();

        if($chckLibrary != null){
            $delete = DB::table('ebook')->where('ebookID',$ebookID)->delete();

            $msg = 'Data berhasil dihapus';
        }
        else{
            $status = 422;
            $msg = 'Data gagal dihapus';
        }

        $data = array(
            'status' => $status,
            'message' => $msg
        );

        return response()->json($data, $status);
    }
}
