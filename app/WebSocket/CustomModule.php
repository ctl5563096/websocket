<?php declare(strict_types=1);


namespace App\WebSocket;

use Swoft\Http\Message\Request;
use Swoft\Http\Message\Response;
use Swoft\Redis\Redis;
use Swoft\Session\Session;
use Swoft\WebSocket\Server\Annotation\Mapping\OnClose;
use Swoft\WebSocket\Server\Annotation\Mapping\OnHandshake;
use Swoft\WebSocket\Server\Annotation\Mapping\OnOpen;
use Swoft\WebSocket\Server\Annotation\Mapping\WsModule;
use Swoole\WebSocket\Server;

/**
 * Class CustomModule
 * @package App\WebSocket
 *
 * 客服聊天websocket
 *
 * @WsModule(
 *     "/Custom",
 *     messageParser=JsonParser::class,
 * )
 *
 */
class CustomModule
{
    /**
     * 握手方法
     *
     * Date: 2020/6/19
     * @param Request $request
     * @param Response $response
     * @return array
     * @author chentulin
     *
     * @OnHandshake()
     */
    public function onHandshake(Request $request , Response $response): array
    {
        $data = $request->getQueryParams();

        //检测是否携带客服id
        if (isset($data['custom_id'])){
            return [true ,$response];
        }

        return [false ,$response];
    }

    /**
     * 连接成功回调方法
     *
     * @OnOpen()
     * @param Request $request
     * @param Response $response
     * @return array
     */
    public function onOpen(Request $request, Response $response)
    {
        // 获取前端提交过来的客服Id 以客服Id为哈希键 文件描述符fd为哈希值
        $data = $request->getQueryParams();
        $hashKey = Session::mustGet()->get('customId');
        if($hashKey){
            server()->push($request->getFd(), "该客服已经被登录,请确认账号密码是否被盗用!");
            return [false ,$response];
        }else{
            Session::mustGet()->set('customId',(string)$data['custom_id']);
            // 把fd存储到哈希里面
            if(Redis::hGet('customList',(string)$data['custom_id'])){
                server()->push($request->getFd(), "该客服已经被登录,请确认账号密码是否被盗用@_@!");
                return [false ,$response];
            }else{
                Redis::hSet('customList', (string)$data['custom_id'] , (string)$request->getFd());
            }
        }
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
        // 断开websocket时删除客服信息
        $hashKey = Session::mustGet()->get('customId');
        Redis::hDel('customList', $hashKey);
    }
}
