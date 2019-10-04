<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Constants\ResponseConstants;
use App\Http\Utils\ResponseException;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionFacade
{

    public function __construct()
    { }

    public function doTransaction(Request $request)
    {
        // get loan fee
        $loanFee = $this->doCheckLoanFee($request->libraryID);
        $amountLoan = count($request->bookID) * $loanFee;

        if ($loanFee == null) {
            // check validation library setting on system
            throw new ResponseException(ResponseConstants::TRANSACTION_LIBRARY_SETTING_NOT_EXIST);
        } else if ($this->doCheckMemberPremium($request->memberID) == null) {
            // check validation member premium
            throw new ResponseException(ResponseConstants::TRANSACTION_MEMBER_NOT_PREMIUM);
        } else if ($this->doCheckSaldoMember($request->memberID) < $amountLoan ) {
            // check validation saldo member with member amount transaction
            throw new ResponseException(ResponseConstants::TRANSACTION_INSUFFICIENT_SALDO);
        }else if ($this->doCheckLoanExist($request->memberID)) {
            // check validation email exist on system
            throw new ResponseException(ResponseConstants::TRANSACTION_LOAN_ALREADY_EXIST);
        } else {
            try {
    
                DB::beginTransaction();

                // insert into table transaction loan
                $this->doInsertTransaction($request);

                // update saldo member
                $this->doUpdateSaldoMember($request->memberID, $amountLoan);

                // update saldo library
                $this->doUpdateSaldoLibrary($request->libraryID, $amountLoan);

                //insert into table log payment_loan
                $this->doInsertLogPayment($request, $loanFee);

                //insert into table member saldo log
                $this->doInsertMemberSaldoLog($request->memberID, $amountLoan);

                //insert into table library saldo log
                $this->doInsertLibrarySaldoLog($request->libraryID, $amountLoan);

                //insert into table mbaku saldo log
                $this->doInsertMbakuSaldoLog($amountLoan);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception($e);
            }
        }
    }

    public function doReturnTransaction(Request $request)
    {
        $libraryID = $this->doCheckLibrary($request->transactionLoanID);

        try {
            DB::beginTransaction();

            //check validation is overdue
            if($request->overDueFee > 0){
                //check validation payment type
                if ($request->paymentType == "e-wallet")
                {
                    if ($this->doCheckSaldoMember($request->memberID) < $request->overDueFee ) {
                        // check validation saldo member with member over due fee
                        throw new ResponseException(ResponseConstants::TRANSACTION_INSUFFICIENT_SALDO);
                        exit();
                    }else{
                        // update saldo member
                        $this->doUpdateSaldoMember($request->memberID, $request->overDueFee);

                        // update saldo library
                        $this->doUpdateSaldoLibrary($libraryID, $request->overDueFee, $isOverDue ='true');

                        //insert into table member saldo log
                        $this->doInsertMemberSaldoLog($request->memberID, $request->overDueFee);

                        //insert into table library saldo log
                        $this->doInsertLibrarySaldoLog($libraryID, $request->overDueFee, $overDue='true');
                    }

                } else {
                    //insert into table member saldo log
                    $this->doInsertMemberSaldoLog($request->memberID, $request->overDueFee, $paymentType='tunai');

                    //insert into table library saldo log
                    $this->doInsertLibrarySaldoLog($libraryID, $request->overDueFee, $overDue='true', $paymentType = 'tunai');
                }
            }

            //update transaction to return
            $this->doReturn($request);
        
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    private function doCheckMemberPremium($memberID)
    {
        $isNotPremium = DB::table('member_premium')->where("memberID", '=', $memberID)->where('memberApproval', 1)->first();
        return $isNotPremium;
    }

    private function doCheckLoanExist($memberID)
    {
        $isHasLoan = DB::table('transaction_loan')->where("memberID", '=', $memberID)->where('transactionLoanStatus', 0)->first();
        return $isHasLoan;
    }
    
    private function doCheckLoanFee($libraryID)
    {
        $loanFee = null;

        $checkSetting = DB::table('setting')->select('settingValue')->where('libraryID',$libraryID)->first();

        if($checkSetting){
            $settingValue = json_decode($checkSetting->settingValue);
            $loanFee = $settingValue->loanFee;
        }
        
        return $loanFee;
    }

    private function doCheckDueDateFee($libraryID)
    {
        $checkSetting = DB::table('setting')->select('settingValue')->where('libraryID',$libraryID)->first();
        $settingValue = json_decode($checkSetting->settingValue);
        $dueDateFee = $settingValue->dueDateFee;
        
        return $dueDateFee;
    }

    private function doCheckLibrary($transactionLoanID)
    {
        $checkLibrary = DB::table('transaction_loan')->where('transactionLoanID', $transactionLoanID)->first();
        $libraryID = $checkLibrary->libraryID;
        return $libraryID;
    }

    private function doCheckSaldoMember($memberID)
    {
        $checkSaldo = DB::table('member_premium')->where("memberID", '=', $memberID)->where('memberApproval', 1)->value('memberPremiumSaldo');
        
        return $checkSaldo;
    }

    private function doInsertTransaction($transaction)
    {
        $date = date('Ymd');

        if (count($transaction->bookID) > 0) {
            foreach ($transaction->bookID as $key => $value) {
                $tempData[$key]['libraryID'] = $transaction->libraryID;
                $tempData[$key]['memberID'] = $transaction->memberID;
                $tempData[$key]['bookID'] = $value['bookID'];
                $tempData[$key]['transactionLoanDate'] = $date;
                $tempData[$key]['transactionLoanDueDate'] = $transaction->transactionLoanDueDate;
                $this->updateStokBook('loanBook',$value['bookID']);
            }

            $save = DB::table('transaction_loan')->insert($tempData);
    
        }
    }

    private function updateStokBook($transaction,$bookID)
    {
        $getStok = DB::table('book')->select('bookStock')->where('bookID',$bookID)->first();

        if($transaction == 'returnBook'){
            $stock = $getStok->bookStock + 1;
        }
        else{
            $stock = $getStok->bookStock - 1;
        }

        $query = DB::table('book')->where('bookID',$bookID)->update(['bookStock' => $stock]);
    }

    private function doUpdateSaldoMember($memberID, $amountLoan)
    {
        //get saldo member
        $memberSaldo = DB::table('member_premium')->where('memberID', $memberID)->where('memberApproval', 1)->value('memberPremiumSaldo');
        //total kredit saldo member
        $total = $memberSaldo - $amountLoan;
        //update saldo member
        $updateSaldoMember = DB::table('member_premium')->where('memberID', $memberID)->where('memberApproval', 1)->update(['memberPremiumSaldo' => $total]);
    }

    private function doUpdateSaldoLibrary($libraryID, $amountLoan, $isOverDue = 'false')
    {
        $total = 0;
        //get saldo library
        $librarySaldo = DB::table('library')->where('libraryID', $libraryID)->value('librarySaldo');
        
        if($isOverDue == 'true'){
            //total debit saldo library
            $total = $librarySaldo + $amountLoan;
        } else {
            //total fee sharing to mbaku 10%
            $feeSharing = $amountLoan - ($amountLoan * 0.1);
            //total debit saldo library
            $total = $librarySaldo + $feeSharing;
        }
        
        //update saldo library
        $updateSaldolibrary = DB::table('library')->where('libraryID', $libraryID)->update(['librarySaldo' => $total]);
    }

    private function doInsertLogPayment($payment, $loanFee)
    {
        $dateTime = date('Ymdhis');

        if (count($payment->bookID) > 0) {
            foreach ($payment->bookID as $key => $value) {
                $tempData2[$key]['libraryID'] = $payment->libraryID;
                $tempData2[$key]['memberID'] = $payment->memberID;
                $tempData2[$key]['bookID'] = $value['bookID'];
                $tempData2[$key]['amount'] = $loanFee;
                $tempData2[$key]['paymentLoanDateTime'] = $dateTime;
            }
            $payment = DB::table('payment_loan')->insert($tempData2);
        }
    }

    private function doInsertMemberSaldoLog($memberID, $amountLoan, $paymentType = 'dompet mbaku')
    {
        DB::table('member_saldo_log')->insert([
            'memberID' => $memberID,
            'nominal' => $amountLoan,
            'saldoLogType' => 'Kredit',
            'paymentType' => $paymentType,
        ]);
    }

    private function doInsertLibrarySaldoLog($libraryID, $amountLoan, $overDue='false', $paymentType = 'dompet mbaku')
    {
        $debitLibrary = $amountLoan;

        if($overDue=='false'){
            //total fee sharing to mbaku 10%
            $debitLibrary = $amountLoan - ($amountLoan * 0.1);
        }
        
        DB::table('library_saldo_log')->insert([
            'libraryID' => $libraryID,
            'nominal' => $debitLibrary,
            'saldoLogType' => 'Debit',
            'paymentType' => $paymentType,
        ]);
    }

    private function doInsertMbakuSaldoLog($amountLoan)
    {
        //insert fee to mbaku_saldo_log
        $fee = $amountLoan * 0.1;
        
        DB::table('mbaku_saldo_log')->insert([
            'nominal' => $fee,
            'saldoLogType' => 'Debit',
            'paymentType' => 'dompet mbaku',
        ]);   
    }

    private function doReturn($return)
    {
        $date = date('Ymd');

        if (count($return->bookID) > 0) {
            foreach ($return->bookID as $key => $value) {
                $this->updateStokBook('returnBook',$value['bookID']);
            }

            $returnBook = DB::table('transaction_loan')->whereIn('transactionLoanID',$return->transactionLoanID)->update(["transactionLoanReturnDate" => $date, "transactionLoanStatus" => 1]);
        }
    }
}
