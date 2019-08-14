<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class RegisterController extends Controller
{
    public function __construct()
    {
        $this->member = DB::table('member');
        $this->users = DB::table('users');
    }

    public function registerUser(Request $request)
    {
        $date = date('Y-m-d');
        $data = [];
        $msg = '';
        $status = 200;
        $uuid = Str::uuid();
        $checkExistMember = $this->users->where("email", $request->email)->first();
        $memberID = 'member-'.$uuid.'-'.time();

        if (!$checkExistMember) {
            $saveMember = $this->member->insert([
                'memberID' => $memberID,
                'memberFirstName' => $request->firstName,
                'memberLastName' => $request->lastName,
                'memberGender' => $request->gender || null,
                'memberPhone' => $request->phone,
                'memberEmail' => $request->email,
                'memberAddress' => $request->address || null,
                'memberPhoto' => $request->photo || null,
                'memberRole' => 0,
                'memberJoinDate' => $date,
                'updatedAt' => null,
            ]);

            if ($saveMember) {
                $users = $this->users->insert([
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'role' => 0,
                ]);

                if ($users) {
                    $msg = 'success';
                } else {
                    $status = 500;
                }
            } else {
                $status = 500;
            }
        } else {
            $msg = 'Email sudah terdaftar';
            $status = 401;
        }

        $data = array(
            'msg' => $msg,
            'status' => $status,
        );

        return response()->json($data, $status);

    }
}
