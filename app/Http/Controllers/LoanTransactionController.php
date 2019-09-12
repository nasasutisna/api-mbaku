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
}
