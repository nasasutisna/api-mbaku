<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $checkExistMember = $this->users->where("email", $request->email)->first();

        if (!$checkExistMember) {
            $saveMember = $this->member->insert([
                'memberID' => $request->memberID,
                'memberFirstName' => $request->firstName,
                'memberLastName' => $request->lastName,
                'memberGender' => $request->gender,
                'memberPhone' => $request->phone,
                'memberEmail' => $request->email,
                'memberAddress' => $request->address,
                'memberPhoto' => $request->photo,
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
