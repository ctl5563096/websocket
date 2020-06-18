<?php declare(strict_types=1);


namespace App\Rpc\Lib;

/**
 *
 * Interface CustomInterface
 * @package App\Rpc\Lib
 *
 * @since 2.0
 */
interface CustomInterface
{
    public function send(int $customId ,string $type,string $message ,string $openId): bool;
}
