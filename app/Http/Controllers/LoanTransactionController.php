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
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease', 'book.bookCover');
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
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease', 'book.bookCover');
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
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease', 'book.bookCover');
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
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease', 'book.bookCover');
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
                        'book.bookTitle', 'book.bookWriter', 'book.bookRelease', 'book.bookCover');
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
        $query->select('category.categoryTitle', 'ebook_rentals.*', 'ebook.*');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedBackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as feedback');
        $query->leftjoin('ebook', 'ebook.ebookID', '=',  'ebook_rentals.ebookID');
        $query->leftjoin('category', 'category.categoryID', '=', 'ebook.categoryID');
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
        $query->select('category.categoryTitle', 'feedback.*', 'ebook.*');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedBackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as feedback');
        $query->leftjoin('ebook', 'ebook.ebookID', '=',  'feedback.ebookID');
        $query->leftjoin('category', 'category.categoryID', '=', 'ebook.categoryID');
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
