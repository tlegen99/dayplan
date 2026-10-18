<?php

declare(strict_types=1);

namespace Framework\Health;

use Framework\Database\Connection;

final class PostgresHealthCheck implements HealthCheck
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function name(): string
    {
        return 'postgres';
    }

    public function check(): void
    {
        $this->connection->query('SELECT 1');
    }
}
