<?php declare(strict_types=1);


namespace App\Rpc\Service;


use App\Rpc\Lib\CustomInterface;
use Swoft\Redis\Redis;
use Swoft\Rpc\Server\Annotation\Mapping\Service;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * Class CustomService
 * @package App\Rpc\Service
 *
 * @since 2.0
 *
 * @Service()
 */
class CustomService implements CustomInterface
{
    /**
     * 发送信息到指定客服的聊天室
     *
     * Date: 2020/6/18
     * @param int $customId 客服ID
     * @param string $type
     * @param string $message 发送的信息
     * @param string $openId 用户的openId
     * @return bool
     * @author chentulin
     */
    public function send(int $customId ,string $type, string $message, string $openId): bool
    {
        // 获取客服的fd
        $fd   = Redis::hGet('customList', (string)$customId);

        // 判断客服是否在线
        if ($fd === false){
            return false;
        }
        // 发送数据体type用于判断发送的是图片地址还是文本
        $data = [
            'type'    => $type,
            'openId'  => $openId,
            'message' => $message,
        ];
        return server()->push((int)$fd , JsonHelper::encode($data));
    }
}
