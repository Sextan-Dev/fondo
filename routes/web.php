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
Auth::routes(['register' => false]);

Route::get('login', function () {
    return view('app');
})->where(['all' => '.*']);

Route::any('{all}', function () {
    return view('app');
})->where(['all' => '.*']);

Route::get('download/{query}' , 'ContentController@downloadFile' )->middleware('jwt.auth')->name('downloadFile')->where('query','.+');
