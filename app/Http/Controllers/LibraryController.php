<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    public $tbl_library = 'library';
    public $tbl_regencies = 'regencies';
    public $tbl_provinces = 'provinces';

    public function __construct()
    {

    }

    public function getListLibrary(Request $request)
    {
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $keyword = $request->input('keyword');

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);
        $query = DB::table($this->tbl_library)
                ->select('library.*','university.*','regencies.name as city')
                ->leftJoin('university','university.universityID','=','library.universityID')
                ->leftJoin('regencies','regencies.id','=','library.libraryCity');

        if($keyword){
            $query = $query->where('libraryName','like','%'.$keyword.'%');
        }

        $query->skip($skip);
        $query->limit($limit);

        $total = DB::table('library')->count();
        $totalPage = ceil($total / $limit);

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

    public function getDetailLibrary($id)
    {
        $libraryID = $id;

        $query = DB::table($this->tbl_library);
        $query->select('library.*', 'university.universityName','regencies.name as libraryCity');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->leftjoin('regencies', 'regencies.id', '=', 'library.libraryCity');
        $query->where('libraryID', $libraryID);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $msg = '';
        $status = 200;
        $libraryPhotoName = '';
        $libraryMapsRoomName = '';
        $date = date('Ymdhis');

        $libraryPhoto = $request->file('libraryPhoto');
        if ($libraryPhoto) {
            $libraryPhotoName = str_replace(' ', '_', $date . '_' . $libraryPhoto->getClientOriginalName());
            $libraryPhoto->storeAs('public/library', $libraryPhotoName);
        }

        $libraryMapsRoom = $request->file('libraryMapsRoom');
        if ($libraryMapsRoom) {
            $libraryMapsRoomName = str_replace(' ', '_', $date . '_mapsroom_' . $libraryMapsRoom->getClientOriginalName());
            $libraryMapsRoom->storeAs('public/library', $libraryMapsRoomName);
        }

        // check action add or edit true if its edit
        $isUpdate = $request->input('isUpdate');

        $universityID = $request->input('universityID');
        $libraryName = $request->input('libraryName');
        $libraryEmail = $request->input('libraryEmail');
        $libraryPhone = $request->input('libraryPhone');
        $libraryAddress = $request->input('libraryAddress');
        $libraryCity = $request->input('libraryCity');
        $libraryProvince = $request->input('libraryProvince');
        $libraryLatLong = $request->input('libraryLatLong');

        $content = array(
            'universityID' => $universityID,
            'libraryName' => $libraryName,
            'libraryEmail' => $libraryEmail,
            'libraryPhone' => $libraryPhone,
            'libraryAddress' => $libraryAddress,
            'libraryCity' => $libraryCity,
            'libraryProvince' => $libraryProvince,
            'libraryLatLong' => $libraryLatLong,
            'libraryPhoto' => $libraryPhotoName,
            'libraryMapsRoom' => $libraryMapsRoomName,
            'libraryJoinDate' => date('Y-m-d'),
        );

        if ($isUpdate == 'true') {
            $libraryID = $request->input('libraryID');
            DB::table($this->tbl_library)->where('libraryID', $libraryID)->update($content);
        } else {
            DB::table($this->tbl_library)->insert($content);
        }

        $msg = 'Data berhasil disimpan';

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function getNearby(Request $request)
    {
        $city = $request->city;
        $province = $request->province;
        $getCity = DB::table($this->tbl_regencies)->where('name', 'like', '%' . $city . '%')->get();

        if (count($getCity) > 0) {

            $cityID = $getCity[0]->id;
            $getNearby = DB::table($this->tbl_library)->where('libraryCity', $cityID)->get();

            if (count($getNearby) == 0) {
                $getProvince = DB::table($this->tbl_provinces)->where('name', 'like', '%' . $province . '%')->get();

                if (count($getProvince) > 0) {

                    $provinceID = $getProvince[0]->id;
                    $getNearby = DB::table($this->tbl_library)->where('libraryProvince', $provinceID)->get();

                    if (count($getNearby) == 0) {
                        $getNearby = DB::table($this->tbl_library)->limit(5)->get();
                    }

                } else {
                    $getNearby = DB::table($this->tbl_library)->limit(5)->get();
                }
            }
        } else {
            $getProvince = DB::table($this->tbl_provinces)->where('name', 'like', '%' . $province . '%')->get();
            if (count($getProvince) > 0) {

                $provinceID = $getProvince[0]->id;
                $getNearby = DB::table($this->tbl_library)->where('libraryProvince', $provinceID)->get();

                if (count($getNearby) == 0) {
                    $getNearby = DB::table($this->tbl_library)->limit(5)->get();
                }

            } else {
                $getNearby = DB::table($this->tbl_library)->limit(5)->get();
            }
        }

        $data = json_decode(json_encode($getNearby), true);

        return response()->json($data, 200);
    }
}
