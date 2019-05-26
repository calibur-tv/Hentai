<?php /** @noinspection ALL */

/** @noinspection PhpUndefinedNamespaceInspection */


namespace App\Services;

use App\User;
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
        // fd、reactorId：https://wiki.swoole.com/wiki/page/56.html
        // 使用 swoole table 在每次 server 重启后都会被清空导致用户掉线
    }

    // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的
    public function onOpen(Server $server, Request $request)
    {
        $token = $request->get['token'];
        if (!$token)
        {
            return;
        }

        $user = User
            ::where('api_token', $request->get['token'])
            ->first();
        if (is_null($user))
        {
            return;
        }

        $userId = $user->id;

        // 记录这个 table 是为在 onMessage 的时候让别人找到当前用户的 fd
        app('swoole')
            ->wsTable
            ->set('uid:' . $userId, ['value' => $request->fd]);
        // 记录这个 table 是为了在 onClose 的时候找到当前用户的 uid
        app('swoole')
            ->wsTable
            ->set('fd:' . $request->fd, ['value' => $userId]);

        $server->push($request->fd, json_encode([
            'hola' => 'Welcome to calibur.tv'
        ]));
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // $frame：https://wiki.swoole.com/wiki/page/987.html
        // 信息发送者的 fd 用 frame->fd 可以知道
        // 但是接收者的 fd 不知道，只知道接受者的 uid
        // 要根据接受者 uid 找到他的 fd
        $data = json_decode($frame->data, true);
        $targetUid = $data['target_id'];
        $targetFd = app('swoole')->wsTable->get('uid:' . $targetUid);
        if ($targetFd === false)
        {
            return;
        }

        $server->push($targetFd['value'], json_encode($data));
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        // 1. 通过当前 fd 找到这个用户的 uid
        // 2. 如果 uid 存在，就删除 uid 的 table，这样就不会再接收 message
        // 3. 删除当前用户的 fd，释放内存，并且该 fd 讲会被其他人复用
        $uid = app('swoole')->wsTable->get('fd:' . $fd);
        if ($uid !== false)
        {
            app('swoole')
                ->wsTable
                ->del('uid:' . $uid['value']);
        }
        app('swoole')
            ->wsTable
            ->del('fd:' . $fd);
    }

    /* https://wiki.swoole.com/wiki/page/397.html
    public function onRequest(Request $request, Response $response)
    {

    }
    */
}
