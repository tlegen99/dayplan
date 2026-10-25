<?php

declare(strict_types=1);

namespace Framework\Core;

use App\Health\HealthController;
use Framework\Config\EnvLoader;
use Framework\Container\Container;
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
    public readonly Container $container;

    public function __construct(private readonly string $projectDir)
    {
        $this->init();
    }

    private function init(): void
    {
        $this->container = new Container();

        $logger = new FileLogger($this->projectDir . '/storage/logs/app.log');

        $errorHandler = new ErrorHandler($logger, filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL));
        $errorHandler->register();

        new EnvLoader($this->projectDir)->load();
        $env = getenv();

        $this->container->set(LoggerInterface::class, fn() => $logger);

        $this->container->set(DatabaseConfig::class, fn ($c) => DatabaseConfig::fromEnv($env));
        $this->container->set(RedisConfig::class, fn ($c) => RedisConfig::fromEnv($env));

        $this->container->set(Connection::class, fn ($c) => new Connection($c->get(DatabaseConfig::class)));
        $this->container->set(RedisConnection::class, fn ($c) => new RedisConnection($c->get(RedisConfig::class)));

        $this->container->set(PostgresHealthCheck::class, fn ($c) => new PostgresHealthCheck($c->get(Connection::class)));
        $this->container->set(RedisHealthCheck::class, fn ($c) => new RedisHealthCheck($c->get(RedisConnection::class)));

        $this->container->set(HealthCheckRunner::class, fn ($c) => new HealthCheckRunner(
            [$c->get(PostgresHealthCheck::class), $c->get(RedisHealthCheck::class)],
        ));
    }
}
