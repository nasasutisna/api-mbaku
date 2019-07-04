<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AnggotaController extends Controller
{
    var $tbl_anggota = 'anggota';

    public function __construct()
    {
        //
    }

    public function createAnggota(Request $request)
    {
        $msg = "";
        $results = array();
        $data = array();

        $kode_anggota = $request->input('kode_anggota');
        $nama_lengkap = $request->input('nama_lengkap');
        $email = $request->input('email');
        $nomor_handphone = $request->input('nomor_handphone');
        $password = bcrypt($request->input('password'));

        $anggota = DB::table('anggota');
        $check = $anggota->where('kode_anggota','=',$kode_anggota)->first();

        if($check){
            $msg = "NIM / NIK sudah terdaftar";
            $results = array (
                'msg' => $msg
            );
            return response()->json($results,500);
        }
        else{
            $msg = "Berhasil mendaftar!";
            $record = array(
                'kode_anggota' => $kode_anggota,
                'nama_lengkap' => $nama_lengkap,
                'email' => $email,
                'nomor_handphone' => $nomor_handphone,
            );

            $save_anggota = $anggota->insert($record);

            if($save_anggota){
                $record_user = array(
                    'email' => $email,
                    'password' => $password,
                    'status' => 0,
                );
                $save_users = DB::table('users')->insert($record_user);
            }

            $results = array (
                'msg' => $msg,
                'data' => $save_anggota
            );

            return response()->json($results);
        }
    }

    public function updateAnggota(Request $request)
    {
        $msg = "";
        $status = 200;
        $results = array();
        $data = array();

        $photo = $request->file("photo");
        if($photo){
            $filename = $photo->getClientOriginalName();
            $storePhoto = $photo->storeAs('public/profile', $filename);
        }

        $serial_id = $request->input('serial_id');
        $kode_anggota = $request->input('kode_anggota');
        $nama_lengkap = $request->input('nama_lengkap');
        $email = $request->input('email');
        $alamat = $request->input('alamat');
        $status = $request->input('status');
        $nomor_handphone = $request->input('nomor_handphone');

        // print_r($request->all());
        $anggota = DB::table('anggota');

            $msg = ($serial_id) ? "Berhasil diperbarui!" : "Berhasil disimpan!" ;

            $record = array(
                'kode_anggota' => $kode_anggota,
                'nama_lengkap' => $nama_lengkap,
                'email' => $email,
                'nomor_handphone' => $nomor_handphone,
                'alamat' => $alamat,
            );

            if(isset($status)){
                $record['status'] = $status;
            }

            if($photo){
                $record['photo'] = 'storage/app/public/profile/'.$filename;
            }

            if($serial_id == 0 || $serial_id == 'undefined'){
                $save_anggota = $anggota->insert($record); // create new
            }
            else{
                $getUser = DB::table('anggota')->where('kode_anggota',$kode_anggota)->first(); // update
                $old_email = $getUser->email;
                $update_user = DB::table('users')->Where('email',$old_email)->update(['email' => $email]);
                $save_anggota = $anggota->where('serial_id',$serial_id)->orWhere('kode_anggota',$kode_anggota)->update($record); // update

            }

            $results = array (
                'msg' => $msg,
                'data' => $save_anggota
            );

            return response()->json($results);

    }


    public function registerAccount(Request $request){
        // define result
        $msg = '';
        $status = 200;
        $data = [];

        // define table
        $anggota = DB::table('anggota');
        $users = DB::table('users');

        // define request input
        $email = $request->input('email');
        $password = bcrypt($request->input('password'));

        // check email anggota
        $check_email = $anggota->where('email','=',$email)->first();

        if($check_email){
            // check email users
            $check_email_user = $users->where('email','=',$email)->first();
            if($check_email_user){
                $status = 500;
                $msg = 'Email sudah terdaftar';
            }
            else{
              $record = array(
                  'email' => $email,
                  'password' => $password
              );

              // save users
              $save_user = $users->insert($record);
              $msg = 'Berhasil mendaftar';
            }
        }
        else{
            $status = 500;
            $msg = 'Email belum terdaftar, silahkan daftar sebagai anggota';
        }

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data,$status);
    }

    public function getDetail($id){
        $data = [];
        $anggota = DB::table($this->tbl_anggota);
        $data = $anggota->where('serial_id',$id)->orWhere('kode_anggota',$id)->first();
        return response()->json($data, 200);
    }

    public function delete($id){
        $anggota = DB::table($this->tbl_anggota);
        $data = $anggota->where('serial_id',$id)->delete();
        return response()->json($data, 200);
    }

    public function getDataAnggota(Request $request)
    {
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ?  $pageIndex : ($pageIndex  * $pageSize);

        $query = DB::table('anggota');

        $count_page = $query->count();

        // searching
        if($keyword != '' && $keyword != 'undefined'){
            $query = $query->where('nama_lengkap', 'like', '%'.$keyword.'%');
            $query = $query->orWhere('kode_anggota', 'like', '%'.$keyword.'%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);
        $query->orderBy('serial_id','desc');

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
}
