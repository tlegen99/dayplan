<?php

declare(strict_types=1);

use Framework\Core\Kernel;
use Framework\Http\JsonResponse;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Router;


/** @var Kernel $kernel */
$kernel = require dirname(__DIR__) . '/bootstrap/app.php';

$request = Request::fromGlobals();

$router = new Router();
$router->get('/health', function (Request $request) use ($kernel): Response {
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

    return new JsonResponse($body, $allHealthy ? 200 : 503);
});

$response = $router->dispatch($request);
$response->send();
