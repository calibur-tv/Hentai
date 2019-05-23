<?php


namespace App\Services;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
/**
 * @see https://wiki.swoole.com/wiki/page/400.html
 */
class WebSocketService implements WebSocketHandlerInterface
{
    // 声明没有参数的构造函数
    public function __construct()
    {

    }

    // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的
    public function onOpen(Server $server, Request $request)
    {
        $userId = mt_rand(1000, 10000);
        app('swoole')->wsTable->set('uid:' . $userId, ['value' => $request->fd]);   // 绑定uid到fd的映射
        app('swoole')->wsTable->set('fd:' . $request->fd, ['value' => $userId]);    // 绑定fd到uid的映射
        $server->push($request->fd, 'Welcome to LaravelS');
    }

    public function onMessage(Server $server, Frame $frame)
    {
        foreach (app('swoole')->wsTable as $key => $row)
        {
            if (strpos($key, 'uid:') === 0 && $server->exist($row['value']))
            {
                $server->push($row['value'], 'Broadcast: ' . date('Y-m-d H:i:s'));  // 广播
            }
        }
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        $uid = app('swoole')->wsTable->get('fd:' . $fd);
        if ($uid !== false)
        {
            app('swoole')->wsTable->del('uid:' . $uid['value']);                    // 解绑uid映射
        }
        app('swoole')->wsTable->del('fd:' . $fd);                                   // 解绑fd映射
        $server->push($fd, 'Goodbye');
    }

    /* https://wiki.swoole.com/wiki/page/397.html
    public function onRequest(Request $request, Response $response)
    {

    }
    */
}
