<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;
use DateTime;

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
        $this->library_saldo_log = DB::table('library_saldo_log');
        $this->member_saldo_log = DB::table('member_saldo_log');
    }

    public function getBookLoan($id)
    {
        $memberID = $id;
        $overDueDay = 0;
        $dueDateFee = 0;
        $settingValue = '';

        $checkTransaction = DB::table('transaction_loan')->where('memberID', $memberID)->where('transactionLoanStatus', 0)->first();

        if($checkTransaction != null){

            $libraryID = $checkTransaction->libraryID;
            $due =  $checkTransaction->transactionLoanDueDate;
            $dueDate = new DateTime($due);
            $currentDate = new DateTIME(date('Ymd'));

            //validate book loan is overdue or no
            if($currentDate > $dueDate){
                $dueTotal = $dueDate->diff($currentDate);
                $overDueDay = $dueTotal->days;

                $checkSetting = DB::table('setting')->select('settingValue')->where('libraryID',$libraryID)->first();

                if($checkSetting){
                    $settingValue = json_decode($checkSetting->settingValue);
                    $dueDateFee = $settingValue->dueDateFee;
                }
            }
        }

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
        $overDueFee = $overDueDay * $dueDateFee * $total;
        $book = json_decode(json_encode($query), true);

        $data = array(
            'book' => $book,
            'bookTotal' => $total,
            'overdueDay' => $overDueDay,
            'overDueFee' => $overDueFee
        );

        return response()->json($data);
    }

    public function getBookLoanHistory(Request $request)
    {
        $memberID = $request->input('memberID');
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

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

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);

        $query = $query->get();

        $data = array(
            'book' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'booktotal' => $total,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }

    public function getBookLoanOverdue(Request $request)
    {
        $libraryID = $request->input('libraryID');
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $currentDate = date('Ymd');

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

        $query = $this->transaction_loan;
        $query->select('transaction_loan.*', 'library.libraryName', 'library.libraryAddress', 'university.universityName',
                        'member.memberFirstName', 'member.memberLastName', 'member.memberPhone',
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease' );
        $query->leftjoin('library', 'library.libraryID', '=', 'transaction_loan.libraryID');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->leftjoin('member', 'member.memberID', '=', 'transaction_loan.memberID');
        $query->leftjoin('book', 'book.bookID', '=', 'transaction_loan.bookID');
        $query->where('transaction_loan.libraryID', $libraryID);
        $query->where('transactionLoanStatus', 0);
        $query->where('transactionLoanDueDate', '<', $currentDate);

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);

        $query = $query->get();

        $data = array(
            'book' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'booktotal' => $total,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }

    public function getBookLoanLibrary(Request $request)
    {
        $libraryID = $request->input('libraryID');
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

        $query = $this->transaction_loan;
        $query->select('transaction_loan.*', 'library.libraryName', 'library.libraryAddress', 'university.universityName',
                        'member.memberFirstName', 'member.memberLastName', 'member.memberPhone',
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease' );
        $query->leftjoin('library', 'library.libraryID', '=', 'transaction_loan.libraryID');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->leftjoin('member', 'member.memberID', '=', 'transaction_loan.memberID');
        $query->leftjoin('book', 'book.bookID', '=', 'transaction_loan.bookID');
        $query->where('transaction_loan.libraryID', $libraryID);
        $query->where('transactionLoanStatus', 0);

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);

        $query = $query->get();

        $data = array(
            'book' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'booktotal' => $total,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }

    public function getLoanHistoryLibrary(Request $request)
    {
        $libraryID = $request->input('libraryID');
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

        $query = $this->transaction_loan;
        $query->select('transaction_loan.*', 'library.libraryName', 'library.libraryAddress', 'university.universityName',
                        'member.memberFirstName', 'member.memberLastName', 'member.memberPhone',
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease' );
        $query->leftjoin('library', 'library.libraryID', '=', 'transaction_loan.libraryID');
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->leftjoin('member', 'member.memberID', '=', 'transaction_loan.memberID');
        $query->leftjoin('book', 'book.bookID', '=', 'transaction_loan.bookID');
        $query->where('transaction_loan.libraryID', $libraryID);
        $query->where('transactionLoanStatus', 1);

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);

        $query = $query->get();

        $data = array(
            'book' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'booktotal' => $total,
            'totalPage' => $totalPage,
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

    // public function loanTransaction(Request $request)
    // {
    //     $msg = '';
    //     $status = 200;
    //     $tempData = [];
    //     $date = date('Ymd');

    //     $libraryID = $request->input('libraryID');
    //     $memberID = $request->input('memberID');
    //     $bookID = $request->input('bookID');
    //     $dueDate = $request->input('transactionLoanDueDate');

    //     $member = $this->member_premium->where('memberID', $memberID)->where('memberApproval', 1)->first();
    //     $library = $this->library->where('libraryID', $libraryID)->first();
    //     $checkLoanBook = $this->transaction_loan->where('memberID', $memberID)->where('transactionLoanStatus', 0)->first();
    //     $checkSetting = DB::table('setting')->select('settingValue')->where('libraryID',$libraryID)->first();
    //     $settingValue = json_decode($checkSetting->settingValue);

    //     //validation loanbook
    //     if($checkLoanBook != null){
    //         $status = 422;
    //         $msg = 'anggota ini sedang meminjam buku';
    //     }
    //     else{
    //         //validation member premium
    //         if ($member == null){
    //             $status = 422;
    //             $msg = 'anggota ini bukan akun premium';
    //         }
    //         else{
    //             //totalKredit = bookTotal * loanFee
    //             $totalKredit = count($bookID) * $settingValue->loanFee;

    //             //validation member saldo
    //             if($member->memberPremiumSaldo >= $totalKredit){
    //                 DB::beginTransaction();

    //                 //validate when insert and update to DB is error or no
    //                 try {
    //                     //  input book loan transaction
    //                     if (count($bookID) > 0) {
    //                         foreach ($bookID as $key => $value) {
    //                             $tempData[$key]['libraryID'] = $libraryID;
    //                             $tempData[$key]['memberID'] = $memberID;
    //                             $tempData[$key]['bookID'] = $value['bookID'];
    //                             $tempData[$key]['transactionLoanDate'] = $date;
    //                             $tempData[$key]['transactionLoanDueDate'] = $dueDate;
    //                             $this->updateStokBook('loanBook',$value['bookID']);
    //                         }

    //                         $save = DB::table('transaction_loan')->insert($tempData);

    //                         //kredit & update saldo member
    //                         $memberSaldo = $member->memberPremiumSaldo -  $totalKredit;
    //                         $updateMember = $this->member_premium->where('memberID', $memberID)->where('memberApproval', 1)->update(['memberPremiumSaldo' => $memberSaldo]);

    //                         //debit & update saldo library
    //                         $debitLibrary = $totalKredit - ( $totalKredit * 0.1 ); //10% fee for MBAKU
    //                         $librarySaldo = $library->librarySaldo + $debitLibrary;
    //                         $updateLibrary = DB::table('library')->where('libraryID', $libraryID)->update(['librarySaldo' => $librarySaldo]);

    //                         //insert log payment book loan
    //                         $dateTime = date('Ymdhis');
    //                         if (count($bookID) > 0) {
    //                             foreach ($bookID as $key => $value) {
    //                                 $tempData2[$key]['libraryID'] = $libraryID;
    //                                 $tempData2[$key]['memberID'] = $memberID;
    //                                 $tempData2[$key]['bookID'] = $value['bookID'];
    //                                 $tempData2[$key]['amount'] = $settingValue->loanFee;
    //                                 $tempData2[$key]['paymentLoanDateTime'] = $dateTime;
    //                             }
    //                             $payment = DB::table('payment_loan')->insert($tempData2);
    //                         }

    //                         //insert member_saldo_log
    //                         DB::table('member_saldo_log')->insert([
    //                             'memberID' => $memberID,
    //                             'nominal' => $totalKredit,
    //                             'saldoLogType' => 'Kredit',
    //                             'paymentType' => 'dompet mbaku',
    //                         ]);

    //                         //insert library_saldo_log
    //                         DB::table('library_saldo_log')->insert([
    //                             'libraryID' => $libraryID,
    //                             'nominal' => $debitLibrary,
    //                             'saldoLogType' => 'Debit',
    //                             'paymentType' => 'dompet mbaku',
    //                         ]);

    //                         //insert fee to mbaku_saldo_log
    //                         $fee = $totalKredit * 0.1;
    //                         DB::table('mbaku_saldo_log')->insert([
    //                             'nominal' => $fee,
    //                             'saldoLogType' => 'Debit',
    //                             'paymentType' => 'dompet mbaku',
    //                         ]);

    //                         DB::commit(); // all good

    //                         $msg = 'Transaksi peminjaman berhasil';
    //                     }
    //                 }
    //                 catch (\Exception $e) {
    //                     DB::rollback(); // something went wrong

    //                     $status = 422;
    //                     $msg = 'Transaksi peminjaman gagal';
    //                 }
    //             }
    //             else{
    //                 $status = 422;
    //                 $msg = 'saldo member tidak cukup';
    //             }

    //         }

    //     }

    //     $data = array(
    //         'status' => $status,
    //         'message' => $msg
    //     );

    //     return response()->json($data, $status);
    // }

    // public function returnTransaction(Request $request)
    // {
    //     $msg = '';
    //     $status = 200;
    //     $tempData = [];
    //     $date = date('Ymd');

    //     $paymentType = $request->input("paymentType");
    //     $transactionLoanID = $request->input('transactionLoanID');
    //     $overDueFee = $request->input('overDueFee');
    //     $memberID = $request->input('memberID');
    //     $bookID = $request->input('bookID');

    //     DB::beginTransaction();

    //     //validate when insert and update to DB is error or no
    //     try {
    //         //validation transaction is overdue or not
    //         if($overDueFee > 0){
    //             $library = $this->transaction_loan->where('transactionLoanID', $transactionLoanID)->first();
    //             $libraryID = $library->libraryID;

    //             //validation payment type
    //             if($paymentType == "e-wallet"){
    //                 $getMember = $this->member_premium->where('memberID', $memberID)->where('memberApproval', 1)->first();
    //                 $memberSaldo = $getMember->memberPremiumSaldo;

    //                 //validation member saldo
    //                 if($memberSaldo >= $overDueFee){
    //                     //kredit saldo member
    //                     $kredit = $memberSaldo - $overDueFee;
    //                     $updateMember = $this->member_premium->where('memberID', $memberID)->where('memberApproval', 1)->update(['memberPremiumSaldo' => $kredit]);

    //                     //debet saldo library
    //                     $librarySaldo = $this->library->where('libraryID', $libraryID)->first();
    //                     $debet = $overDueFee + $librarySaldo->librarySaldo;
    //                     $updateLibrary = DB::table('library')->where('libraryID', $libraryID)->update(['librarySaldo' => $debet]);

    //                     //insert member_saldo_log
    //                     DB::table('member_saldo_log')->insert([
    //                         'memberID' => $memberID,
    //                         'nominal' => $overDueFee,
    //                         'saldoLogType' => 'Kredit',
    //                         'paymentType' => 'dompet mbaku',
    //                     ]);

    //                     //insert library_saldo_log
    //                     DB::table('library_saldo_log')->insert([
    //                         'libraryID' => $libraryID,
    //                         'nominal' => $overDueFee,
    //                         'saldoLogType' => 'Debit',
    //                         'paymentType' => 'dompet mbaku',
    //                     ]);

    //                 }
    //                 else{
    //                     $data = array(
    //                         'status' => 422,
    //                         'message' => 'saldo member tidak cukup',
    //                     );

    //                     return response()->json($data);
    //                     exit();
    //                 }
    //             }
    //             //payment Type == cash
    //             else{

    //                 //insert member_saldo_log
    //                 DB::table('member_saldo_log')->insert([
    //                     'memberID' => $memberID,
    //                     'nominal' => $overDueFee,
    //                     'saldoLogType' => 'Kredit',
    //                     'paymentType' => 'tunai',
    //                 ]);

    //                 //insert library_saldo_log
    //                 DB::table('library_saldo_log')->insert([
    //                     'libraryID' => $libraryID,
    //                     'nominal' => $overDueFee,
    //                     'saldoLogType' => 'Debit',
    //                     'paymentType' => 'tunai',
    //                 ]);
    //             }
    //         }

    //         // return book transaction
    //         if (count($bookID) > 0) {
    //             foreach ($bookID as $key => $value) {
    //                 $this->updateStokBook('returnBook',$value['bookID']);
    //             }

    //             $returnBook = DB::table('transaction_loan')->whereIn('transactionLoanID',$transactionLoanID)->update(["transactionLoanReturnDate" => $date, "transactionLoanStatus" => 1]);
    //         }

    //         DB::commit(); // all good

    //         $msg = 'Pengembalian buku berhasil';
    //     }
    //     catch (\Exception $e) {
    //         DB::rollback(); // something went wrong

    //         $status = 422;
    //         $msg = 'Pengembalian buku gagal';
    //     }

    //     $data = array(
    //         'status' => $status,
    //         'message' => $msg
    //     );

    //     return response()->json($data, $status);

    // }

    public function updateStokBook($transaction,$bookID)
    {
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

    public function logSaldo(Request $request)
    {
        $id = $request->input('id');
        $role = $request->input('role');
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);

        if($role == 'member'){
            $query = $this->member_saldo_log;
            $query->where('memberID', $id);
        }
        else{
            $query = $this->library_saldo_log;
            $query->where('libraryID', $id);
        }

        $total = $query->count();
        $totalPage = ceil($total / $limit);

        $query->skip($skip);
        $query->limit($limit);
        $query->orderBy('createdAt', 'desc');

        $query = $query->get();

        $data = array(
            'data' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'total' => $total,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }
}
