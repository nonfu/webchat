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
    return view('index');
});

Route::get('/test', function () {
    return 'hello world!';
});

Route::get('/socket.io', 'SocketIOController@upgrade');
Route::post('/socket.io', 'SocketIOController@ok');

Route::get('download', function() {
    return response()->streamDownload(function(){
        echo file_get_contents('https://github.com/nonfu/laravel-resources/raw/master/pkgs/laravel58.zip');
    }, 'laravel58.zip');
});
