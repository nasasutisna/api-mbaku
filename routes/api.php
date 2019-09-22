<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1', 'middleware' => ['auth:api']], function () {
    Route::get('book/getPopularBook', 'BookController@getPopularBook');
    Route::post('book/getBookList', 'BookController@getBookList');
    Route::post('ebook/getEbookList', 'EbookController@getEbookList');
    Route::get('book/searchTitle', 'BookController@searchTitle');
    Route::get('ebook/searchTitle', 'EbookController@searchTitle');
    Route::post('ebook/store', 'EbookController@store');
    Route::get('ebook/delete/{id}', 'EbookController@deleteEbook');
    Route::post('book/store', 'BookController@store');
    Route::get('book/detail/{id}', 'BookController@getDetailBook');
    Route::get('ebook/detail/{id}', 'EbookController@getDetailEbook');
    Route::post('ebook/checkAccessRead', 'EbookController@checkAccessRead');
    Route::get('library/detail/{id}', 'LibraryController@getDetailLibrary');
    Route::post('library/store', 'LibraryController@store');
    Route::get('bookLoan/user/{id}', 'LoanTransactionController@getBookLoan');
    Route::post('bookLoan/library', 'LoanTransactionController@getBookLoanLibrary');
    Route::post('loanHistory/library', 'LoanTransactionController@getLoanHistoryLibrary');
    Route::post('logSaldo', 'LoanTransactionController@logSaldo');
    Route::post('loanHistory/user', 'LoanTransactionController@getBookLoanHistory');
    Route::post('loanOverdue', 'LoanTransactionController@getBookLoanOverdue');
    Route::post('loanTransaction', 'LoanTransactionController@loanTransaction');
    Route::post('returnTransaction', 'LoanTransactionController@returnTransaction');
    Route::get('ebookRental/user/{id}', 'LoanTransactionController@getEbookRental');
    Route::get('ebookWishlist/user/{id}', 'LoanTransactionController@getEbookWishlist');
    Route::get('userBanner/{id}', 'MemberController@userBanner');
    Route::get('book/getNewBook', 'BookController@getNewBook');
    Route::post('library/getNearby', 'LibraryController@getNearby');
    Route::post('library/getListLibrary', 'LibraryController@getListLibrary');
    Route::get('library/delete/{id}', 'LibraryController@deleteLibrary');
    Route::get('book/getMostSearch', 'BookController@getMostSearch');
    Route::post('category/getCategory', 'CategoryController@getCategory');
    Route::get('logout', 'LoginController@logout');
    Route::post('payment/ebook', 'PaymentController@purchase');
    Route::post('ebook/getEbook', 'EbookController@getEbook');
    Route::post('ebook/checkMyFeedBack', 'EbookController@checkMyFeedBack');
    Route::post('ebook/addFeedBack', 'EbookController@addFeedBack');
    Route::get('member/detail/{id}', 'MemberController@getDetail');
    Route::post('member/update', 'MemberController@updateMember');
    Route::post('member/profile/upload', 'MemberController@updatePhotoProfile');
    Route::post('payment/ebook/save', 'PaymentController@savePaymentEbook');
    Route::post('member/upgrade', 'MemberController@upgradeUserPremium');
    Route::post('member/uploadPhotoKTP', 'MemberController@uploadPhotoKTP');
    Route::post('member/checkMemberStatus', 'MemberController@checkMemberStatus');
    Route::post('member/topUpSaldo', 'MemberController@topUpSaldo');
    Route::post('member/saveSaldo', 'MemberController@saveSaldo');
    Route::post('member/getStatusPayment', 'MemberController@getStatusPayment');
});

Route::group(['prefix' => 'v1'], function () {
    Route::post('login', 'LoginController@processLogin');
    Route::get('login/generateToken', 'LoginController@generateToken');
    Route::get('invalidToken', 'LoginController@invalidToken')->name('invalid-token');
    Route::post('register', 'Register\RegisterController@registerUser');
    Route::get('register/verify/{id}', 'Register\RegisterController@verifyUser');

    //Route::get('email/resend', 'VerificationApiController@resend')->name('verificationapi.resend');
    Route::get('member/rejected/{memberPremiumID}', 'MemberController@memberRejected');
    Route::get('member/approved/{memberPremiumID}', 'MemberController@memberApproved');
});



    // Route::get('dashboard/getBookList','DashboardController@getDataBook');
    // Route::get('anggota','AnggotaController@getDataAnggota');
    // Route::get('transaksi','TransaksiController@getDataTransaction');
    // Route::get('dashboard/admin','DashboardController@dashboardAdmin');
    // Route::get('dashboard/getCategories','DashboardController@getDataCategory');
    // Route::get('book/detail/{id}','BukuController@getDetailBook');
    // Route::get('book/getPopularBook','BukuController@getPopularBook');
    // Route::post('book/getEbook','BukuController@getEbook');
    // Route::post('book/store','BukuController@store');
    // Route::post('book/addRatting','BukuController@addRatting');
    // Route::post('book/checkMyRate','BukuController@checkMyRate');
    // Route::get('book/getBookByCategory/{id}','BukuController@getBookByCategory');
    // Route::post('login','LoginController@processLogin');
    // Route::post('anggota/daftar','AnggotaController@createAnggota');
    // Route::delete('anggota/delete/{id}','AnggotaController@delete');
    // Route::delete('book/delete/{id}','BukuController@delete');
    // Route::post('anggota/account/register','AnggotaController@registerAccount');
    // Route::post('anggota/account/update','AnggotaController@updateAnggota');
    // Route::get('anggota/account/detail/{kode_anggota}','AnggotaController@getDetail');
    // Route::post('transaksi/order','TransaksiController@purchase');
    // Route::get('transaksi/status/{id}','TransaksiController@getOrderStatus');
    // Route::get('transaksi/order/cancel/{order_id}','TransaksiController@cancelOrder');
    // Route::post('transaksi/order/pending','TransaksiController@checkExistsTransaction');
    // Route::post('transaksi/store','TransaksiController@store');
    // Route::post('transaksi/getListByAnggota','TransaksiController@getListByAnggota');
    // Route::post('transaksi/order/store','TransaksiController@saveOrder');
    // Route::post('transaksi/order/update','TransaksiController@updateStatusOrder');
    // Route::post('transaksi/order/book/pending','TransaksiController@orderBookPending');
    // Route::get('transaksi/order/getByAnggota/{kode_anggota}','TransaksiController@getOrderByAnggota');
