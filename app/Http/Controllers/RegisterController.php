<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function registerUser(Request $request)
    {
        $date=date('Y-m-d');
        $data = [];
        $msg = '';
        $status = 200;
        
        $verify = $this->validate($request, [
            'firstName' => 'required',
            'gender' => 'required',
            'phone' => 'required||min:11',
            'address' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed', 
        ]);

        if ($verify){
            $member = DB::table('member')->insert([
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
                'updatedAt' => null
            ]);

            $users = DB::table('users')->insert([
                'email' => $request->email,
                'password'=> bcrypt($request->password),
                'role' => 0
            ]);

            $msg = 'success';
         }
         else{
            $msg = 'fail';
            $status = 401;
         }

         $data = array(
            'msg' => $msg,
            'status' => $status
        );

        return response()->json($data);

    }
}
