<?php

declare(strict_types=1);

namespace Framework\Health;

use Framework\Redis\RedisConnection;

final class RedisHealthCheck implements HealthCheck
{
    public function __construct(
        private readonly RedisConnection $connection,
    ) {}

    public function name(): string
    {
        return 'redis';
    }

    public function check(): void
    {
        if (!$this->connection->ping()) {
            throw new \RuntimeException('PING returned false');
        }
    }
}
