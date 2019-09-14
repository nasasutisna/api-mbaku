<?php

namespace App\Http\Controllers;

use App\Library;
use App\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function GuzzleHttp\json_decode;

class LibraryController extends Controller
{
    public $tbl_library = 'library';
    public $tbl_setting = 'setting';
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
            ->select('library.*', 'university.*', 'regencies.name as city')
            ->leftJoin('university', 'university.universityID', '=', 'library.universityID')
            ->leftJoin('regencies', 'regencies.id', '=', 'library.libraryCity');

        if ($keyword) {
            $query = $query->where('libraryName', 'like', '%' . $keyword . '%');
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
        $query->select('library.*', 'university.universityName', 'regencies.name as libraryCity');
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

        $libraryPhoto = ($request->file('libraryPhoto')) ? $request->file('libraryPhoto') : "";
        if ($libraryPhoto) {
            $libraryPhotoName = str_replace(' ', '_', $date . '_' . $libraryPhoto->getClientOriginalName());
            $libraryPhoto->storeAs('public/library', $libraryPhotoName);
        }

        $libraryMapsRoom =($request->file('libraryMapsRoom')) ?  $request->file('libraryMapsRoom') : "";
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

        $library = new Library();

        if ($isUpdate == 'true') {
            $libraryID = $request->input('libraryID');
            $update = $library::find($libraryID);

            $update->universityID = $universityID;
            $update->libraryName = $libraryName;
            $update->libraryEmail = $libraryEmail;
            $update->libraryPhone = $libraryPhone;
            $update->libraryAddress = $libraryAddress;
            $update->libraryCity = $libraryCity;
            $update->libraryProvince = $libraryProvince;
            $update->libraryLatLong = $libraryLatLong;
            $update->libraryPhoto = $libraryPhotoName;
            $update->libraryMapsRoom = $libraryMapsRoomName;
            $update->libraryJoinDate = date('Y-m-d');

            $save = $update->save();
            // DB::table($this->tbl_library)->where('libraryID', $libraryID)->update($content);
        } else {
            $library->universityID = $universityID;
            $library->libraryName = $libraryName;
            $library->libraryEmail = $libraryEmail;
            $library->libraryPhone = $libraryPhone;
            $library->libraryAddress = $libraryAddress;
            $library->libraryCity = $libraryCity;
            $library->libraryProvince = $libraryProvince;
            $library->libraryLatLong = $libraryLatLong;
            $library->libraryPhoto = $libraryPhotoName;
            $library->libraryMapsRoom = $libraryMapsRoomName;
            $library->libraryJoinDate = date('Y-m-d');

            $save = $library->save();

            $setOpHours = ($request->setOpHour) ? $request->setOpHour : "";
            $loanFee = ($request->loanFee) ? $request->loanFee : 0;
            $dueDateFee = ($request->dueDateFee) ? $request->dueDateFee : 0;
            $calculateDueDate = ($request->calculateDueDate) ? $request->calculateDueDate : ""; // day , week , mounth, year

            $loanFee = $request->loanFee;

            if ($save) {
                $setting = new Setting;

                $arrData = Array(
                    'operationalHours' =>  $setOpHours,
                    'loanFee' =>  $loanFee,
                    'dueDateFee' =>  $dueDateFee,
                    'calculateDueDate' =>  $calculateDueDate,
                );

                $setting->libraryID = $library->id;
                $setting->settingValue = json_encode($arrData);
                $setting->save();
            }
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
            $getNearby = DB::table($this->tbl_library)->where('libraryCity', $cityID);

            if ($getNearby->count() == 0) {
                $getProvince = DB::table($this->tbl_provinces)->where('name', 'like', '%' . $province . '%')->get();

                if (count($getProvince) > 0) {

                    $provinceID = $getProvince[0]->id;
                    $getNearby = DB::table($this->tbl_library)->where('libraryProvince', $provinceID);

                    if ($getNearby->count() == 0) {
                        $getNearby = DB::table($this->tbl_library)->limit(5);
                    }

                } else {
                    $getNearby = DB::table($this->tbl_library)->limit(5);
                }
            }
        } else {
            $getProvince = DB::table($this->tbl_provinces)->where('name', 'like', '%' . $province . '%')->get();
            if (count($getProvince) > 0) {

                $provinceID = $getProvince[0]->id;
                $getNearby = DB::table($this->tbl_library)->where('libraryProvince', $provinceID);
                if ($getNearby->count() == 0) {
                    $getNearby = DB::table($this->tbl_library)->limit(5);
                }

            } else {
                $getNearby = DB::table($this->tbl_library)->limit(5);
            }
        }

        $getNearby = $getNearby->leftjoin('setting','setting.libraryID','=','library.libraryID');
        $getNearby = $getNearby->get();


        $data = json_decode(json_encode($getNearby), true);
        $data = $this->convertSetting($data);

        return response()->json($data, 200);
    }

    public function convertSetting($data){
        foreach($data as $key=>$value){
            $data[$key]['settingValue'] = $value['settingValue'] != null ? json_decode($value['settingValue']) : $value['settingValue'];
        }

        return $data;
    }
}
