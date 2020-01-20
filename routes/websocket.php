<?php
use App\Count;
use App\User;
use App\Message;
use Carbon\Carbon;
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
    $websocket->loginUsing(auth('api')->user());
});

WebsocketProxy::on('room', function (WebSocket $websocket, $data) {
    if ($userId = $websocket->getUserId()) {
        $user = User::find($userId);
        // 从请求数据中获取房间ID
        if (empty($data['roomid'])) {
            return;
        }
        $roomId = $data['roomid'];
        // 重置用户与fd关联
        Redis::hset('socket_id', $user->id, $websocket->getSender());
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

WebsocketProxy::on('message', function (WebSocket $websocket, $data) {
    if ($userId = $websocket->getUserId()) {
        $user = User::find($userId);
        // 获取消息内容
        $msg = $data['msg'];
        $img = $data['img'];
        $roomId = intval($data['roomid']);
        $time = $data['time'];
        // 消息内容或房间号不能为空
        if((empty($msg)  && empty($img))|| empty($roomId)) {
            return;
        }
        // 记录日志
        Log::info($user->name . '在房间' . $roomId . '中发布消息: ' . $msg);
        // 将消息保存到数据库（图片消息除外，因为在上传过程中已保存）
        if (empty($img)) {
            $message = new Message();
            $message->user_id = $user->id;
            $message->room_id = $roomId;
            $message->msg = $msg;  // 文本消息
            $message->img = '';  // 图片消息留空
            $message->created_at = Carbon::now();
            $message->save();
        }
        // 将消息广播给房间内所有用户
        $room = Count::$ROOMLIST[$roomId];
        $messageData = [
            'userid' => $user->email,
            'username' => $user->name,
            'src' => $user->avatar,
            'msg' => $msg,
            'img' => $img,
            'roomid' => $roomId,
            'time' => $time
        ];
        $websocket->to($room)->emit('message', $messageData);
        // 更新所有用户本房间未读消息数
        $userIds = Redis::hgetall('socket_id');
        foreach ($userIds as $userId => $socketId) {
            // 更新每个用户未读消息数并将其发送给对应在线用户
            $result = Count::where('user_id', $userId)->where('room_id', $roomId)->first();
            if ($result) {
                $result->count += 1;
                $result->save();
                $rooms[$room] = $result->count;
            } else {
                // 如果某个用户未读消息数记录不存在，则初始化它
                $count = new Count();
                $count->user_id = $user->id;
                $count->room_id = $roomId;
                $count->count = 1;
                $count->save();
                $rooms[$room] = 1;
            }
            $websocket->to($socketId)->emit('count', $rooms);
        }
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
});

WebsocketProxy::on('roomout', function (WebSocket $websocket, $data) {
    roomout($websocket, $data);
});

WebsocketProxy::on('disconnect', function (WebSocket $websocket, $data) {
    roomout($websocket, $data);
    $websocket->logout();
});

function roomout(WebSocket $websocket, $data) {
    if ($userId = $websocket->getUserId()) {
        $user = User::find($userId);
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
    if ($userId = $websocket->getUserId()) {
        $user = User::find($userId);
        // 将用户与指定fd连接关联起来保存到Redis中
        Redis::hset('socket_id', $user->id, $websocket->getSender());
        // 获取未读消息
        $rooms = [];
        foreach (Count::$ROOMLIST as $id => $name) {
            // 循环所有房间
            $result = Count::where('user_id', $user->id)->where('room_id', $id)->first();
            if ($result) {
                $rooms[$name] = $result->count;
            } else {
                $count = new Count();
                $count->user_id = $user->id;
                $count->room_id = $id;
                $count->count = 0;
                $count->save();
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
