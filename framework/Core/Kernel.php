<?php

declare(strict_types=1);

namespace Framework\Core;

use Framework\Config\EnvLoader;
use Framework\Database\Connection;
use Framework\Database\DatabaseConfig;
use Framework\Health\HealthCheckRunner;
use Framework\Health\PostgresHealthCheck;
use Framework\Health\RedisHealthCheck;
use Framework\Http\ErrorHandler;
use Framework\Logger\FileLogger;
use Framework\Logger\LoggerInterface;
use Framework\Redis\RedisConfig;
use Framework\Redis\RedisConnection;

final class Kernel
{
    public readonly LoggerInterface $logger;
    public readonly Connection $db;
    public readonly RedisConnection $redis;
    public readonly HealthCheckRunner $health;

    public function __construct(private readonly string $projectDir)
    {
        $this->init();
    }

    private function init(): void
    {
        $this->logger = new FileLogger($this->projectDir . '/storage/logs/app.log');

        $errorHandler = new ErrorHandler($this->logger, filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL));
        $errorHandler->register();

        new EnvLoader($this->projectDir)->load();
        $env = getenv();

        $dbConfig = DatabaseConfig::fromEnv($env);
        $redisConfig = RedisConfig::fromEnv($env);

        $this->db = new Connection($dbConfig);
        $this->redis = new RedisConnection($redisConfig);

        $this->health = new HealthCheckRunner([
            new PostgresHealthCheck($this->db),
            new RedisHealthCheck($this->redis),
        ]);
    }
}
