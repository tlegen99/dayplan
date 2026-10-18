<?php

declare(strict_types=1);

namespace Framework\Redis;

final class RedisConnection
{
    private ?\Redis $redis = null;

    public function __construct(
        private readonly RedisConfig $config,
    ) {}

    private function client(): \Redis
    {
        if ($this->redis !== null) {
            return $this->redis;
        }

        try {
            $redis = new \Redis();
            $connected = $redis->connect(
                $this->config->host,
                $this->config->port,
                $this->config->connectTimeout
            );

            if ($connected === false) {
                throw RedisUnavailableException::connectFailed($this->config);
            }

            $this->redis = $redis;

            return $this->redis;
        } catch (\RedisException $e) {
            throw RedisUnavailableException::fromRedisException($e, $this->config);
        }
    }

    public function ping(): bool
    {
        return $this->client()->ping() !== false;
    }
}
