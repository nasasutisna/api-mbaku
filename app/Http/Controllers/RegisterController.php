<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use Auth;
use Validator;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Auth\Events\Verified;

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
        $memberID = mt_rand(100,1000000000).time();

        if (!$checkExistMember) {
            $saveMember = $this->member->insert([
                'memberID' => $memberID,
                'memberFirstName' => $request->firstName,
                'memberLastName' => $request->lastName,
                'memberGender' => ($request->gender) ? $request->gender : '',
                'memberPhone' => $request->phone,
                'memberEmail' => $request->email,
                'memberAddress' => ($request->address) ? $request->address : '',
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

                if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
                    $user = Auth::user();
                    $user->sendApiEmailVerificationNotification();
                }

                if ($users) {
                    $msg = 'verifikasi email berhasil dikirim ke email anda';
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
