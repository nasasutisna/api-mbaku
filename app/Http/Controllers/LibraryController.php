<?php

namespace App\Http\Controllers;

use App\Library;
use App\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Carbon\Carbon;

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
        $getNearby = $getNearby->select('library.*','setting.settingValue');
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

    public function deleteLibrary($id)
    {
        $libraryID = $id;
        $msg = '';
        $status = 200;

        $chckLibrary = DB::table('library')->where('libraryID',$libraryID)->first();

        if($chckLibrary != null){
            $delete = DB::table('library')->where('libraryID',$libraryID)->delete();

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

    public function dashboardLibrary($id)
    {
        $libraryID = $id;
        $msg = '';
        $status = 200;

        date_default_timezone_set('Asia/Jakarta');
        $currentDate1 = new DateTIME(date('Ymd'));
        $currentDate2 = new DateTIME(date('Ymd 23:59:59'));
        $lastWeek = date('Ymd') - 7;
        $thisWeek = new DateTIME(date(''.$lastWeek.' H:i:s'));
        
        try
        {
            $checkLibrary = DB::table('library')->where('libraryID',$libraryID)->first();

            if($checkLibrary != null){
                $saldo =  $checkLibrary->librarySaldo;
                $book = DB::table('book')->where('libraryID',$libraryID);
                $bookTotal = $book->count();

                $loanTransaction = DB::table('transaction_loan')->where('libraryID',$libraryID)->where('transactionLoanStatus', 0)->where('transactionLoanDueDate', '>=', $currentDate2);
                $loanTotal = $loanTransaction->count();

                $overDue = DB::table('transaction_loan')->where('libraryID',$libraryID)->where('transactionLoanStatus', 0)->where('transactionLoanDueDate', '<', $currentDate2);
                $overdueTotal = $overDue->count();

                // ToDay
                $saldoTD = DB::table('library_saldo_log')->where('libraryID',$libraryID)->where('paymentType', 'Mbaku Wallet')->whereBetween('createdAt', [$currentDate1, $currentDate2])->sum('nominal');
                $bookTD = $book->whereBetween('createdAt', [$currentDate1, $currentDate2])->sum('bookTotal');
                $loanTD = DB::table('transaction_loan')->where('libraryID',$libraryID)->where('transactionLoanStatus', 0)->where('transactionLoanDate', $currentDate1)->count();

                //ThisWeek
                $saldoTW = DB::table('library_saldo_log')->where('libraryID',$libraryID)->where('paymentType', 'Mbaku Wallet')->whereBetween('createdAt', [$thisWeek, $currentDate2])->sum('nominal');
                $bookTW = DB::table('book')->where('libraryID',$libraryID)->whereBetween('createdAt', [$thisWeek, $currentDate2])->sum('bookTotal');
                $loanTW = DB::table('transaction_loan')->where('libraryID',$libraryID)->where('transactionLoanStatus', 0)->whereBetween('transactionLoanDate', [$lastWeek, $currentDate1])->count();
                // print_r($bookTW); exit();

                $msg = 'sukses';
            }
            else{
                $status = 422;
                $msg = 'Perpustakaan tidak tersedia';
            }

        }
        catch (\Exception $e) {
            $status = 422;
            $msg = 'terjadi keselahan';
        }
        
        $data = array(
            'status' => $status,
            'message' => $msg,
            'saldo' => $saldo,
            'bookTotal' => $bookTotal,
            'loanTotal' => $loanTotal,
            'overdueTotal' => $overdueTotal,
            'today' => array(
                        'saldo' =>$saldoTD,
                        'bookTotal' => $bookTD,
                        'loanTotal' => $loanTD
                
            ),
            'thisWeek' => array(
                'saldo' =>$saldoTW,
                'bookTotal' => $bookTW,
                'loanTotal' => $loanTW
        
            ),
        );

        return response()->json($data, $status);
    }
}
