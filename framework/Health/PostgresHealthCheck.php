<?php

declare(strict_types=1);

namespace Framework\Health;

use Framework\Database\Connection;

final class PostgresHealthCheck implements HealthCheck
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function check(): HealthResult
    {
        $start = hrtime(true);

        try {
            $this->connection->query('SELECT 1');

            return HealthResult::healthy('postgres', $this->elapsedMs($start));
        } catch (\Throwable $e) {
            return HealthResult::unhealthy('postgres', $this->elapsedMs($start), $e->getMessage());
        }
    }

    private function elapsedMs(int $start): float
    {
        return round((hrtime(true) - $start) / 1_000_000, 2);
    }
}
