<?php

use Swoole\Http\Request;
use App\Services\WebSocket\WebSocket;
use App\Services\Websocket\Facades\Websocket as WebsocketProxy;

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

WebsocketProxy::on('connect', function (WebSocket $websocket, Request $request) {
    // 发送欢迎信息
    $websocket->setSender($request->fd);
    $websocket->emit('connect', '欢迎访问聊天室');

});

WebsocketProxy::on('disconnect', function (WebSocket $websocket) {
    // called while socket on disconnect
});

WebsocketProxy::on('login', function (WebSocket $websocket, $data) {
    if (!empty($data['token']) && ($user = \App\User::where('api_token', $data['token'])->first())) {
        $websocket->loginUsing($user);
        // todo 读取未读消息
        $websocket->toUser($user)->emit('login', '登录成功');
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
});
