<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function settingLibrary($id)
    {
        $libraryID = $id;
        $msg = '';
        $status = 200;

        try
        {
            $getSetting = DB::table('setting')->where('libraryID',$libraryID)->first();

            if($getSetting == null){
                $msg = 'library is not exist';
                $status = 422;
            }
            else{
                $msg = 'success';
                $getSetting = json_decode(json_encode($getSetting), true);
                $getSetting['settingValue'] = json_decode($getSetting['settingValue'],true);
            }

        }
        catch (\Exception $e){
            $msg = 'data request tidak lengkap ';
            $status = 400;
        }

        $data = array(
            'status' => $status,
            'message' => $msg,
            'librarySetting' => $getSetting,
        );

        return response()->json($data);
    }

    public function updateLibrarySetting(Request $request)
    {
        $msg = '';
        $status = 200;

        $settingID = $request->input('settingID');
        $settingValue = $request->input('settingValue');

        try
        {
            $getSetting = DB::table('setting')->where('settingID', $settingID)->first();

            if($getSetting == null){
                $msg = 'perpustakaan tidak tersedia';
                $status = 422;
            }
            else{
                
                $updateSetting = DB::table('setting')->where('settingID', $settingID)->update(['settingValue' => $settingValue]);

                $msg = 'data berhasil disimpan';
                
            }

        }
        catch (\Exception $e){
            $msg = 'data gagal disimpan ';
            $status = 422;
        }

        $data = array(
            'status' => $status,
            'message' => $msg,
        );

        return response()->json($data, $status);
    }
}
