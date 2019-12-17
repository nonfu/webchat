<?php
/**
 * Websocket 相关服务容器绑定和路由加载
 * Author：学院君
 */
namespace App\Events;

use App\Services\WebSocket\Parser;
use App\Services\Websocket\Rooms\RoomContract;
use App\Services\WebSocket\WebSocket;
use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Illuminate\Container\Container;
use Swoole\Http\Server;

class WorkerStartEvent implements WorkerStartInterface
{
    public function __construct()
    {
    }

    public function handle(Server $server, $workerId)
    {
        $isWebsocket = config('laravels.websocket.enable') == true;
        if (!$isWebsocket) {
            return;
        }
        // WorkerStart 事件发生时 Laravel 已经初始化完成，在这里做一些组件绑定到容器的初始化工作最合适
        app()->singleton(Parser::class, function () {
            $parserClass = config('laravels.websocket.parser');
            return new $parserClass;
        });
        app()->alias(Parser::class, 'swoole.parser');

        app()->singleton(RoomContract::class, function () {
            $driver = config('laravels.websocket.drivers.default', 'table');
            $driverClass = config('laravels.websocket.drivers.' . $driver);
            $driverConfig = config('laravels.websocket.drivers.settings.' . $driver);
            $roomInstance = new $driverClass($driverConfig);
            if ($roomInstance instanceof RoomContract) {
                $roomInstance->prepare();
            }
            return $roomInstance;
        });
        app()->alias(RoomContract::class, 'swoole.room');

        app()->singleton(WebSocket::class, function (Container $app) {
            return new WebSocket($app->make(RoomContract::class));
        });
        app()->alias(WebSocket::class, 'swoole.websocket');

        // 引入 Websocket 路由文件
        $routePath = base_path('routes/websocket.php');
        require $routePath;
    }
}
