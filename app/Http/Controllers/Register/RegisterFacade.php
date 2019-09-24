<?php

namespace App\Http\Controllers\Register;

use App\Http\Constants\ResponseConstants;
use App\Http\Utils\ResponseException;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegisterFacade
{

    public function __construct()
    { }

    public function doRegister(Request $request)
    {
        if ($this->doCheckEmailExistRegist($request->email)) {
            // check validation email exist and need verify
            throw new ResponseException(ResponseConstants::REGISTRATION_NEED_VERIFY);
        } else if ($this->doCheckEmailExist($request->email)) {
            // check validation email exist on system
            throw new ResponseException(ResponseConstants::REGISTRATION_EMAIL_ALREADY_EXISTS);
        } else {
            try {
                // get current timestamp
                $currentTimestamp = Carbon::now()->toDateTimeString();

                // generate signature
                $signature = $this->gen_uuid();

                // generate memberID
                $memberID = mt_rand(100, 1000000000) . time();

                DB::beginTransaction();

                // insert into table registration
                $this->doInsertRegistration($request, $memberID, $signature, $currentTimestamp);

                // insert data into email_queue table
                $this->doInsertEmailQueue($request, $request->getSchemeAndHttpHost(), $memberID, $signature);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception($e);
            }
        }
    }

    public function doVerify($memberId, $signature)
    {
        try {
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $user = DB::table('registration')->where('memberID', '=', $memberId)->get()->first();
            if (!$user) {
                // if user with member id not exists
                ResponseConstants::VERIFY_USER_NOT_FOUND;
            } else {
                if (md5($user->signature . $user->memberID) != $signature) {
                    // if user signature key not valid
                    throw new ResponseException(ResponseConstants::VERIFY_SIGNATURE_INVALID);
                } else if ($currentTimestamp > $user->expiryAt) {
                    // if link activation is expired
                    throw new ResponseException(ResponseConstants::VERIFY_USER_EXPIRY);
                } else {
                    DB::beginTransaction();

                    // insert data into member table
                    $this->doInsertMember($user, $currentTimestamp);

                    // insert data into user table
                    $this->doInsertUser($user, $currentTimestamp);

                    // do delete data from registration table
                    $this->doDeleteVerifyUser($user->memberID);

                    DB::commit();
                }
            }
        } catch (ResponseException $e) {
            DB::rollBack();
            throw new ResponseException($e);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    private function doDeleteVerifyUser($memberID)
    {
        DB::table('registration')->where('memberID', '=', $memberID)->delete();
    }

    private function doInsertMember($member, $currentTimestamp)
    {
        DB::table('member')->insert([
            'memberID' => $member->memberID,
            'memberFirstName' => $member->memberFirstName,
            'memberLastName' => $member->memberLastName,
            'memberGender' => $member->memberGender,
            'memberPhone' => $member->memberPhone,
            'memberEmail' => $member->memberEmail,
            'memberAddress' => $member->memberAddress,
            'memberRole' => $member->memberRole,
            'memberJoinDate' => $currentTimestamp,
            'createdAt' => $member->createdAt,
        ]);
    }

    private function doInsertUser($user, $currentTimestamp)
    {
        return DB::table('users')->insert([
            'email' => $user->memberEmail,
            'email_verified_at' => $currentTimestamp,
            'password' => $user->memberPassword,
            'role' => $user->memberRole,
            'created_at' => $user->createdAt
        ]);
    }

    private function doInsertEmailQueue($emailQueue, $hostName, $memberID, $signature)
    {
        // get param
        $url = $this->generateVerifyRegistUrl($signature, $memberID, $hostName);
        $emailTitle = DB::table('param')->select('paramValue')->where("paramKey", "email.verify.title")->first()->paramValue;
        $emailContent = DB::table('param')->select('paramValue')->where("paramKey", "email.verify.content")->first()->paramValue;

        $emailContent = str_replace('{{name}}', $emailQueue->firstName . ' ' . $emailQueue->lastName, $emailContent);
        $emailContent = str_replace('{{mobilePhone}}', $emailQueue->phone, $emailContent);
        $emailContent = str_replace('{{email}}', $emailQueue->email, $emailContent);
        $emailContent = str_replace('{{url}}', $url, $emailContent);
        $emailContent = str_replace('{{host}}', $hostName, $emailContent);

        // do insert
        return DB::table('email_queue')->insert([
            'emailDest' => $emailQueue->email,
            'emailTitle' => $emailTitle,
            'emailContent' => $emailContent
        ]);
    }

    private function doInsertRegistration($user, $memberID, $signature, $currentTimestamp)
    {
        $expiryDay = DB::table('param')->select('paramValue')->where('paramKey', '=', 'registration.expiry.day')->get()->first();
        $expiryAt = (new DateTime($currentTimestamp))->modify($expiryDay->paramValue . ' day');
        DB::table('registration')->insert([
            'signature' => $signature,
            'memberID' => $memberID,
            'memberFirstName' => $user->firstName,
            'memberLastName' => $user->lastName,
            'memberGender' => $user->gender,
            'memberPhone' => $user->phone,
            'memberEmail' => $user->email,
            'memberAddress' => $user->address,
            'memberPassword' => bcrypt($user->password),
            'memberRole' => 0,
            'createdAt' => $currentTimestamp,
            'expiryAt' => $expiryAt,
        ]);
    }

    private function generateVerifyRegistUrl($signature, $memberID, $hostname)
    {

        return $hostname . '/api/v1/register/verify/' . $memberID . '?signature=' . md5($signature . $memberID);
    }

    private function doCheckEmailExist($email)
    {
        $isExistOnUsers = DB::table('users')->where("email", $email)->first();
        return $isExistOnUsers;
    }

    private function doCheckEmailExistRegist($email)
    {
        $isExistOnRegist = DB::table('registration')->where("memberEmail", '=', $email)->where('expiryAt', '>', Carbon::now()->toDateTimeString())->first();
        return $isExistOnRegist;
    }

    private function gen_uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
