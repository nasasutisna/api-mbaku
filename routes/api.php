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

Route::group(['prefix' => 'v1'], function () {
    Route::get('dashboard/getBookList','DashboardController@getDataBook');
    Route::get('dashboard/getCategories','DashboardController@getDataCategory');
    Route::get('book/detail/{id}','BukuController@getDetailBook');
    Route::get('book/getPopularBook','BukuController@getPopularBook');
    Route::post('book/getEbook','BukuController@getEbook');
    Route::post('book/store','BukuController@store');
    Route::post('book/addRatting','BukuController@addRatting');
    Route::post('book/checkMyRate','BukuController@checkMyRate');
    Route::get('book/getBookByCategory/{id}','BukuController@getBookByCategory');
    Route::post('login','LoginController@processLogin');
    Route::post('anggota/daftar','AnggotaController@createAnggota');
    Route::post('anggota/account/register','AnggotaController@registerAccount');
    Route::post('anggota/account/update','AnggotaController@updateAnggota');
    Route::get('anggota/account/detail/{kode_anggota}','AnggotaController@getDetail');
});
