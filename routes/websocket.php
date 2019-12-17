<?php

use App\Count;
use App\User;
use Swoole\Http\Request;
use App\Services\WebSocket\WebSocket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
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

WebsocketProxy::on('room', function (WebSocket $websocket, $data) {
    if (!empty($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        // 从请求数据中获取房间ID
        if (empty($data['roomid'])) {
            return;
        }
        $roomId = $data['roomid'];
        // 重置用户与fd关联
        Redis::command('hset', ['socket_id', $user->id, $websocket->getSender()]);
        // 将该房间下用户未读消息清零
        $count = Count::where('user_id', $user->id)->where('room_id', $roomId)->first();
        $count->count = 0;
        $count->save();
        // 将用户加入指定房间
        $room = Count::$ROOMLIST[$roomId];
        $websocket->join($room);
        // 打印日志
        Log::info($user->name . '进入房间：' . $room);
        // 更新在线用户信息
        $roomUsersKey = 'online_users_' . $room;
        $onelineUsers = Cache::get($roomUsersKey);
        $user->src = $user->avatar;
        if ($onelineUsers) {
            $onelineUsers[$user->id] = $user;
            Cache::forever($roomUsersKey, $onelineUsers);
        } else {
            $onelineUsers = [
                $user->id => $user
            ];
            Cache::forever($roomUsersKey, $onelineUsers);
        }
        // 广播消息给房间内所有用户
        $websocket->to($room)->emit('room', $onelineUsers);
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
});

WebsocketProxy::on('roomout', function (WebSocket $websocket, $data) {
    roomout($websocket, $data);
});

WebsocketProxy::on('disconnect', function (WebSocket $websocket, $data) {
    roomout($websocket, $data);
});

function roomout(WebSocket $websocket, $data) {
    if (!empty($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        if (empty($data['roomid'])) {
            return;
        }
        $roomId = $data['roomid'];
        $room = Count::$ROOMLIST[$roomId];
        // 更新在线用户信息
        $roomUsersKey = 'online_users_' . $room;
        $onelineUsers = Cache::get($roomUsersKey);
        if (!empty($onelineUsers[$user->id])) {
            unset($onelineUsers[$user->id]);
            Cache::forever($roomUsersKey, $onelineUsers);
        }
        $websocket->to($room)->emit('roomout', $onelineUsers);
        Log::info($user->name . '退出房间: ' . $room);
        $websocket->leave($room);
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
}

WebsocketProxy::on('login', function (WebSocket $websocket, $data) {
    if (!empty($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        // 将用户与指定fd连接关联起来保存到Redis中
        Redis::command('hset', ['socket_id', $user->id, $websocket->getSender()]);
        // 获取未读消息
        $rooms = [];
        foreach (Count::$ROOMLIST as $id => $name) {
            // 循环所有房间
            $result = Count::where('user_id', $user->id)->where('room_id', $id)->first();
            if ($result) {
                $rooms[$name] = $result->count;
            } else {
                $rooms[$name] = 0;
            }
        }
        // 打印日志
        Log::info($user->name . '登录成功');
        // 发送消息给客户端
        $websocket->emit('count', $rooms);
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
});
