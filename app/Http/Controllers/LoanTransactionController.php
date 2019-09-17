<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;

class LoanTransactionController extends Controller
{
    public $tbl_transaction_loan = 'transaction_loan';

    public function __construct()
    {
        $this->transaction_loan = DB::table('transaction_loan');
        $this->ebook_rental = DB::table('ebook_rentals');
        $this->feedback = DB::table('feedback');
        $this->member_premium = DB::table('member_premium');
        $this->library = DB::table('library');
    }

    public function getBookLoan($id)
    {
        $memberID = $id;

        $query = $this->transaction_loan;
        $query->select('transaction_loan.*', 'library.libraryName', 'library.libraryAddress', 'university.universityName',
                        'member.memberFirstName', 'member.memberLastName', 'member.memberPhone',
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease' );
        $query->leftjoin('library', 'library.libraryID', '=', 'transaction_loan.libraryID');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->leftjoin('member', 'member.memberID', '=', 'transaction_loan.memberID');
        $query->leftjoin('book', 'book.bookID', '=', 'transaction_loan.bookID');
        $query->where('transaction_loan.memberID', $memberID);
        $query->where('transactionLoanStatus', 0);

        $query = $query->get();
        $total = count($query);
        $book = json_decode(json_encode($query), true);

        $data = array(
            'book' => $book,
            'bookTotal' => $total
        );

        return response()->json($data);
    }

    public function getBookLoanHistory($id)
    {
        $memberID = $id;

        $query = $this->transaction_loan;
        $query->select('transaction_loan.*', 'library.libraryName', 'library.libraryAddress', 'university.universityName',
                        'member.memberFirstName', 'member.memberLastName', 'member.memberPhone',
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease' );
        $query->leftjoin('library', 'library.libraryID', '=', 'transaction_loan.libraryID');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->leftjoin('member', 'member.memberID', '=', 'transaction_loan.memberID');
        $query->leftjoin('book', 'book.bookID', '=', 'transaction_loan.bookID');
        $query->where('transaction_loan.memberID', $memberID);
        $query->where('transactionLoanStatus', 1);

        $query = $query->get();
        $total = count($query);
        $book = json_decode(json_encode($query), true);

        $data = array(
            'book' => $book,
            'bookTotal' => $total
        );

        return response()->json($data);
    }

    public function getEbookRental($id)
    {
        $memberID = $id;

        $query = $this->ebook_rental;
        $query->select('ebook_rentals.*', 'ebook.*');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedBackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as feedback');
        $query->leftjoin('ebook', 'ebook.ebookID', '=',  'ebook_rentals.ebookID');
        $query->where('ebook_rentals.memberID', $memberID);

        $query = $query->get();
        $total = count($query);
        $ebook = json_decode(json_encode($query), true);

        $data = array(
            'ebook' => $ebook,
            'ebookTotal' => $total
        );

        return response()->json($data);
    }

    public function getEbookWishlist($id)
    {
        $memberID = $id;

        $query = $this->feedback;
        $query->select('feedback.*', 'ebook.*');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedBackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as feedback');
        $query->leftjoin('ebook', 'ebook.ebookID', '=',  'feedback.ebookID');
        $query->where('feedback.memberID', $memberID);
        $query->where('feedback.feedBackValue', 1);

        $query = $query->get();
        $total = count($query);
        $ebook = json_decode(json_encode($query), true);

        $data = array(
            'ebook' => $ebook,
            'ebookTotal' => $total
        );

        return response()->json($data);
    }

    public function loanTransaction(Request $request)
    {
        $msg = '';
        $status = 200;
        $tempData = [];
        $date = date('Ymd');

        $transaction = $request->input("transaction");
        $transactionLoanID = $request->input('transactionLoanID');
        $libraryID = $request->input('libraryID');
        $memberID = $request->input('memberID');
        $bookID = $request->input('bookID');
        $dueDate = $request->input('transactionLoanDueDate');

        $tempData = [];
        //validation loanBook or return book
        if ($transaction == 'returnBook') {
            // return book transaction
            if (count($bookID) > 0) {
                foreach ($bookID as $key => $value) {
                  $this->updateStokBook('returnBook',$value['bookID']);
                }

                $returnBook = DB::table('transaction_loan')->whereIn('transactionLoanID',$transactionLoanID)->update(["transactionLoanReturnDate" => $date, "transactionLoanStatus" => 1]);

                if($returnBook){
                    $msg = 'return book is success';
                }
                else{
                    $status = 422;
                    $msg = 'return book is fail';
                }
            }
        }
        else{
            $member = $this->member_premium->where('memberID', $memberID)->first();
            $library = $this->library->where('libraryID', $libraryID)->first();
            $checkLoanBook = $this->transaction_loan->where('memberID', $memberID)->where('transactionLoanStatus', 0)->first();
            $checkSetting = DB::table('setting')->select('settingValue')->where('libraryID',$libraryID)->first();
            $settingValue = json_decode($checkSetting->settingValue);

            //validation loanbook
            if($checkLoanBook != null){
                $status = 422;
                $msg = 'this member is borrowing the book';
            }
            else{
                //totalDebet = bookTotal * loanFee
                $totalDebet = count($bookID) * $settingValue->loanFee;

                //validation member saldo
                if($member->memberPremiumSaldo >= $totalDebet){
                    DB::beginTransaction();

                    try {
                        //  input book loan transaction
                        if (count($bookID) > 0) {
                            foreach ($bookID as $key => $value) {
                                $tempData[$key]['libraryID'] = $libraryID;
                                $tempData[$key]['memberID'] = $memberID;
                                $tempData[$key]['bookID'] = $value['bookID'];
                                $tempData[$key]['transactionLoanDate'] = $date;
                                $tempData[$key]['transactionLoanDueDate'] = $dueDate;
                                $this->updateStokBook('loanBook',$value['bookID']);
                            }

                            $save = DB::table('transaction_loan')->insert($tempData);

                            //debet & update saldo member
                            $memberSaldo = $member->memberPremiumSaldo -  $totalDebet;
                            $updateMember = $this->member_premium->where('memberID', $memberID)->update(['memberPremiumSaldo' => $memberSaldo]);

                            //kredit & update saldo library
                            $kreditLibrary = $totalDebet - ( $totalDebet * 0.1 ); //10% fee for MBAKU
                            $librarySaldo = $library->librarySaldo + $kreditLibrary;
                            $updateLibrary = DB::table('library')->where('libraryID', $libraryID)->update(['librarySaldo' => $librarySaldo]);

                            if($updateMember && $updateLibrary){
                                //insert log payment book loan
                                $dateTime = date('Ymdhis');
                                if (count($bookID) > 0) {
                                    foreach ($bookID as $key => $value) {
                                        $tempData2[$key]['libraryID'] = $libraryID;
                                        $tempData2[$key]['memberID'] = $memberID;
                                        $tempData2[$key]['bookID'] = $value['bookID'];
                                        $tempData2[$key]['amount'] = $settingValue->loanFee;
                                        $tempData2[$key]['paymentLoanDateTime'] = $dateTime;
                                    }
                                    $payment = DB::table('payment_loan')->insert($tempData2);
                                }

                                //insert member_saldo_log
                                DB::table('member_saldo_log')->insert([
                                    'memberID' => $memberID,
                                    'nominal' => $totalDebet,
                                    'saldoLogType' => 'Debet',
                                    'paymentType' => 'Mbaku Wallet',
                                ]);


                                //insert library_saldo_log
                                DB::table('library_saldo_log')->insert([
                                    'libraryID' => $libraryID,
                                    'nominal' => $kreditLibrary,
                                    'saldoLogType' => 'Kredit',
                                    'paymentType' => 'Mbaku Wallet',
                                ]);

                                //insert fee to mbaku_saldo_log
                                $fee = $totalDebet * 0.1;
                                DB::table('mbaku_saldo_log')->insert([
                                    'nominal' => $fee,
                                    'saldoLogType' => 'Kredit Fee',
                                    'paymentType' => 'Mbaku Wallet',
                                ]);     
                            }

                            DB::commit(); // all good
                                
                            $msg = 'loan book is success';
                        }
                    }
                    catch (\Exception $e) {
                        DB::rollback(); //// something went wrong

                        $status = 422;
                        $msg = 'loan book is fail';
                    }
                }
                else{
                    $status = 422;
                    $msg = 'member saldo is not enough';
                }
            }

        }

        $data = array(
            'status' => $status,
            'message' => $msg
        );

        return response()->json($data);
    }

    public function updateStokBook($transaction,$bookID){
        $getStok = DB::table('book')->select('bookStock')->where('bookID',$bookID)->first();
        if($transaction == 'returnBook'){
            $stock = $getStok->bookStock + 1;
        }
        else{
            $stock = $getStok->bookStock - 1;
        }

        $query = DB::table('book')->where('bookID',$bookID)->update(['bookStock' => $stock]);
        return response()->json($query, 200);
    }
}
