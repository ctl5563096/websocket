<?php declare(strict_types=1);
/**
 * This file is part of Swoft.
 *
 * @link     https://swoft.org
 * @document https://swoft.org/docs
 * @contact  group@swoft.org
 * @license  https://github.com/swoft-cloud/swoft/blob/master/LICENSE
 */

namespace App\WebSocket;

use App\WebSocket\Chat\HomeController;
use \Swoft\Redis\Redis;
use Swoft\Http\Message\Request;
use Swoft\WebSocket\Server\Annotation\Mapping\OnClose;
use Swoft\WebSocket\Server\Annotation\Mapping\OnMessage;
use Swoft\WebSocket\Server\Annotation\Mapping\OnOpen;
use Swoft\WebSocket\Server\Annotation\Mapping\WsModule;
use Swoft\WebSocket\Server\MessageParser\JsonParser;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use function server;

/**
 * Class ChatModule
 *
 * @WsModule(
 *     "/Chat",
 *     messageParser=JsonParser::class,
 *     controllers={
 *     HomeController::class
 *     }
 * )
 */
class ChatModule
{
    /**
     * 连接成功回调方法
     *
     * @OnOpen()
     * @param Request $request
     */
    public function onOpen(Request $request): void
    {
        // 把fd存储到哈希里面
        Redis::hSet('weChat_id', 'info' . $request->getFd(), $name = '游客' . time());
        $name = '游客' . time();
        $len = (string)Redis::HLen('weChat_id');
        // 返回客户端fd给客户端,用于处理聊天室问题
        server()->push($request->getFd(), "{$name}欢迎进入聊天室,聊天室有{$len}位小伙伴哦~");
        server()->broadcast("{$name}进入了聊天室,目前聊天室人数{$len}", [], [], $request->getFd());
    }

    /**
     * Date: 2020/5/15
     * @param Frame $frame
     * @author chentulin
     *
     * @OnMessage()
     */
    public function onMessage(Server $server, Frame $frame): void
    {
        // 发送者的fd
        $fd = $frame->fd;
        // 发送者名称
        $name = Redis::hGet('weChat_id', 'info' . $fd);
        server()->sendToAll($name . ' : ' . $frame->data, $fd, 50);
    }

    /**
     * Date: 2020/5/15
     * @param Server $server
     * @param int $fd
     * @author chentulin
     *
     * @OnClose()
     */
    public function onClose(Server $server, int $fd): void
    {
        // 断开socket时删除某个哈希键 防止哈希键过多
        $hashKey = 'info' . $fd;

        Redis::hDel('weChat_id', $hashKey);
    }
}
