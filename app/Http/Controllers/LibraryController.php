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

    public function getDetailLibrary($id)
    {
        $libraryID = $id;

        $query = DB::table($this->tbl_library);
        $query->select('library.*', 'university.universityName');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
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

        $getNearby = json_decode(json_encode($getNearby), true);

        return response()->json($getNearby, 200);
    }
}
