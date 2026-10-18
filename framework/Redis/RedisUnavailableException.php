<?php

declare(strict_types=1);

namespace Framework\Redis;

final class RedisUnavailableException extends \RuntimeException
{
    public static function fromRedisException(\RedisException $e, RedisConfig $config): self
    {
        return new self("Failed to connect to Redis at {$config->host}:{$config->port}", previous: $e);
    }

    public static function connectFailed(RedisConfig $config): self
    {
        return new self("Failed to connect to Redis at {$config->host}:{$config->port} connect() returned false");
    }
}
