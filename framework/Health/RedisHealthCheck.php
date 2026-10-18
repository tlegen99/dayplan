<?php

declare(strict_types=1);

namespace Framework\Health;

use Framework\Redis\RedisConnection;

final class RedisHealthCheck implements HealthCheck
{
    public function __construct(
        private readonly RedisConnection $connection,
    ) {}

    public function check(): HealthResult
    {
        $start = hrtime(true);

        try {
            if (!$this->connection->ping()) {
                return HealthResult::unhealthy('redis', $this->elapsedMs($start), 'PING returned false');
            }

            return HealthResult::healthy('redis', $this->elapsedMs($start));
        } catch (\Throwable $e) {
            return HealthResult::unhealthy('redis', $this->elapsedMs($start), $e->getMessage());
        }
    }

    private function elapsedMs(int $start): float
    {
        return round((hrtime(true) - $start) / 1_000_000, 2);
    }
}
