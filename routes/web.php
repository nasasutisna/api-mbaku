<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verified', function () {
    return view('EmailVerified');
})->name('emailverified');

Route::get('/sendemail', function () {
    $data = [
        'title' => 'When are you coming back?',
        'content' => 'I was in your neighborhood last time and I could no find my way back'
    ];

    Mail::send('emails.test', $data, function($message){

        $message->to('ferimirpan2@gmail.com', 'Prisaizer')->subject('Hi, what\'s up');
    });
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
