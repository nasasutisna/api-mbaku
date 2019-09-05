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

Route::group(['prefix' => 'v1', 'middleware' => ['auth:api'] ], function () {
    Route::get('book/getPopularBook','BookController@getPopularBook');
    Route::post('book/getBookList','BookController@getBookList');
    Route::post('ebook/getEbookList','EbookController@getEbookList');
    Route::get('book/searchTitle','BookController@searchTitle');
    Route::get('ebook/searchTitle','EbookController@searchTitle');
    Route::post('ebook/store','EbookController@store');
    Route::post('book/store','BookController@store');
    Route::get('book/detail/{id}','BookController@getDetailBook');
    Route::get('ebook/detail/{id}','EbookController@getDetailEbook');
    Route::get('library/detail/{id}','LibraryController@getDetailLibrary');
    Route::post('library/store','LibraryController@store');
    Route::get('loanTransaction/user/{id}','LoanTransactionController@getLoanTransaction');
    Route::get('historyTransaction/user/{id}','LoanTransactionController@getHistoryTransaction');
    Route::get('userBanner/{id}','MemberController@userBanner');
    Route::get('book/getNewBook','BookController@getNewBook');
    Route::post('library/getNearby','LibraryController@getNearby');
    Route::post('library/getListLibrary','LibraryController@getListLibrary');
    Route::get('book/getMostSearch','BookController@getMostSearch');
    Route::post('category/getCategory','CategoryController@getCategory');
    Route::get('logout', 'LoginController@logout');
});

Route::group(['prefix' => 'v1'], function () {
    Route::post('login','LoginController@processLogin');
    Route::get('login/generateToken','LoginController@generateToken');
    Route::post('register','RegisterController@registerUser');
    Route::get('invalidToken','LoginController@invalidToken')->name('invalid-token');
    Route::get('email/verify/{id}', 'VerificationApiController@verify')->name('verificationapi.verify');
    Route::get('email/resend', 'VerificationApiController@resend')->name('verificationapi.resend');
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
