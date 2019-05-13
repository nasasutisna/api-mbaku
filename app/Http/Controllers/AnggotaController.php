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

        $kode_anggota = $request->input('kode_anggota');
        $nama_lengkap = $request->input('nama_lengkap');
        $email = $request->input('email');
        $alamat = $request->input('alamat');
        $nomor_handphone = $request->input('nomor_handphone');

        // print_r($request->all());
        $anggota = DB::table('anggota');

            $msg = "Berhasil diperbarui!";
            $record = array(
                'kode_anggota' => $kode_anggota,
                'nama_lengkap' => $nama_lengkap,
                'email' => $email,
                'nomor_handphone' => $nomor_handphone,
                'alamat' => $alamat,
            );

            $save_anggota = $anggota->where('kode_anggota',$kode_anggota)->update($record);

            if(!$save_anggota){
                $msg = "gagal diperbarui!";
                $status = 500;
            }

            $results = array (
                'msg' => $msg,
                'data' => $save_anggota
            );

            return response()->json($results,$status);

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

    public function getDetail($kode_anggota){
        $data = [];
        $anggota = DB::table($this->tbl_anggota);
        $data = $anggota->where('kode_anggota',$kode_anggota)->first();
        return response()->json($data, 200);
    }
}
