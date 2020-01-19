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

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/robot', function (Request $request) {
        $info = $request->get('info');
        $userid = $request->get('id');
        $key = config('services.robot.key');
        $url = config('services.robot.api');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $url, [
            'json' => compact("info", "userid", "key")
        ]);
        return response()->json(['data' => $response->getBody()->getContents()]);
    });
    Route::get('/history/message', 'MessageController@history');
    Route::post('/file/uploadimg', 'FileController@uploadImage');
    Route::post('/file/avatar', 'FileController@avatar');
});
Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
