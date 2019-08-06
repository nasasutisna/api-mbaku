<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public $tbl_member = 'member';

    public function __construct()
    {
        //
    }

    public function createMember(Request $request)
    {
        $msg = "";
        $results = array();
        $data = array();

        $memberID = $request->input('memberID');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $email = $request->input('email');
        $memberPhone = $request->input('memberPhone');
        $password = bcrypt($request->input('password'));

        $member = DB::table('member');
        $check = $member->where('memberID', '=', $memberID)->first();

        if ($check) {
            $msg = "NIM / NIK sudah terdaftar";
            $results = array(
                'msg' => $msg,
            );
            return response()->json($results, 500);
        } else {
            $msg = "Berhasil mendaftar!";
            $record = array(
                'memberID' => $memberID,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'memberPhone' => $memberPhone,
            );

            $save_member = $member->insert($record);

            if ($save_member) {
                $record_user = array(
                    'email' => $email,
                    'password' => $password,
                    'status' => 0,
                );
                $save_users = DB::table('users')->insert($record_user);
            }

            $results = array(
                'msg' => $msg,
                'data' => $save_member,
            );

            return response()->json($results);
        }
    }

    public function updatemember(Request $request)
    {
        $msg = "";
        $status = 200;
        $results = array();
        $data = array();

        $photo = $request->file("photo");
        if ($photo) {
            $filename = $photo->getClientOriginalName();
            $storePhoto = $photo->storeAs('public/profile', $filename);
        }

        $memberSerialID = $request->input('memberSerialID');
        $memberID = $request->input('memberID');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $email = $request->input('email');
        $memberAddress = $request->input('memberAddress');
        $status = $request->input('status');
        $memberPhone = $request->input('memberPhone');

        // print_r($request->all());
        $member = DB::table('member');

        $msg = ($memberSerialID) ? "Berhasil diperbarui!" : "Berhasil disimpan!";

        $record = array(
            'memberID' => $memberID,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'memberPhone' => $memberPhone,
            'memberAddress' => $memberAddress,
        );

        if ($status != 'undefined' && $status == '') {
            $record['status'] = $status;
        }

        if ($photo) {
            $record['photo'] = 'storage/app/public/profile/' . $filename;
        }

        if ($memberSerialID == 0 || $memberSerialID == 'undefined') {
            $save_member = $member->insert($record); // create new
        } else {
            $getUser = DB::table('member')->where('memberID', $memberID)->first(); // update
            $old_email = $getUser->email;
            $update_user = DB::table('users')->Where('email', $old_email)->update(['email' => $email]);
            $save_member = $member->where('memberSerialID', $memberSerialID)->orWhere('memberID', $memberID)->update($record); // update

        }

        $results = array(
            'msg' => $msg,
            'data' => $save_member,
        );

        return response()->json($results);

    }

    public function registerAccount(Request $request)
    {
        // define result
        $msg = '';
        $status = 200;
        $data = [];

        // define table
        $member = DB::table('member');
        $users = DB::table('users');

        // define request input
        $email = $request->input('email');
        $password = bcrypt($request->input('password'));

        // check email member
        $check_email = $member->where('email', '=', $email)->first();

        if ($check_email) {
            // check email users
            $check_email_user = $users->where('email', '=', $email)->first();
            if ($check_email_user) {
                $status = 500;
                $msg = 'Email sudah terdaftar';
            } else {
                $record = array(
                    'email' => $email,
                    'password' => $password,
                );

                // save users
                $save_user = $users->insert($record);
                $msg = 'Berhasil mendaftar';
            }
        } else {
            $status = 500;
            $msg = 'Email belum terdaftar, silahkan daftar sebagai member';
        }

        $data = array(
            'msg' => $msg,
        );

        return response()->json($data, $status);
    }

    public function getDetail($id)
    {
        $data = [];
        $member = DB::table($this->tbl_member);
        $data = $member->where('memberSerialID', $id)->orWhere('memberID', $id)->first();
        return response()->json($data, 200);
    }

    public function delete($id)
    {
        $member = DB::table($this->tbl_member);
        $data = $member->where('memberSerialID', $id)->delete();
        return response()->json($data, 200);
    }

    public function getDatamember(Request $request)
    {
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ? $pageIndex : ($pageIndex * $pageSize);

        $query = DB::table('member');

        $count_page = $query->count();

        // searching
        if ($keyword != '' && $keyword != 'undefined') {
            $query = $query->where('firstName', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('memberID', 'like', '%' . $keyword . '%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);
        $query->orderBy('memberSerialID', 'desc');

        $query = $query->get();
        $query = json_decode(json_encode($query), true);

        $data = array(
            'data' => $query,
            'limit' => $pageSize + 0,
            'page' => $pageIndex + 1,
            'totalPage' => $count_page,
        );

        return response()->json($data);
    }
}
