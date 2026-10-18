<?php

declare(strict_types=1);

use Framework\Core\Kernel;

/** @var Kernel $kernel */
$kernel = require dirname(__DIR__) . '/bootstrap/app.php';

$results = $kernel->health->checkAll();

$allHealthy = true;
$body = [];
foreach ($results as $result) {
    $allHealthy = $allHealthy && $result->healthy;
    $body[$result->name] = [
        'status'      => $result->healthy ? 'ok' : 'fail',
        'duration_ms' => $result->durationMs,
    ];

    if (!$result->healthy) {
        $kernel->logger->log("Health check {$result->name} failed: {$result->error}", 'warning');
    }
}

http_response_code($allHealthy ? 200 : 503);
header('Content-Type: application/json');
echo json_encode($body, JSON_THROW_ON_ERROR);
