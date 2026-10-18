<?php

declare(strict_types=1);

use Framework\Config\EnvLoader;
use Framework\Database\Connection;
use Framework\Database\DatabaseConfig;
use Framework\Health\CompositeHealthCheck;
use Framework\Health\PostgresHealthCheck;
use Framework\Health\RedisHealthCheck;
use Framework\Redis\RedisConfig;
use Framework\Redis\RedisConnection;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envLoader = new EnvLoader(dirname(__DIR__));
$envLoader->load();

$env = getenv();
$dbConfig = DatabaseConfig::fromEnv($env);
$redisConfig = RedisConfig::fromEnv($env);
$dbConnection = new Connection($dbConfig);
$redisConnection = new RedisConnection($redisConfig);

$health = new CompositeHealthCheck([
    new PostgresHealthCheck($dbConnection),
    new RedisHealthCheck($redisConnection),
]);

$results = $health->checkAll();

$allHealthy = true;
$body = [];
foreach ($results as $result) {
    $allHealthy = $allHealthy && $result->healthy;
    $body[$result->name] = [
        'status'      => $result->healthy ? 'ok' : 'fail',
        'duration_ms' => $result->durationMs,
    ];
    // $result->error наружу НЕ отдаём, его будет писать в лог ErrorHandler
}

http_response_code($allHealthy ? 200 : 503);
header('Content-Type: application/json');
echo json_encode($body, JSON_THROW_ON_ERROR);
