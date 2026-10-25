<?php

declare(strict_types=1);

use App\Health\HealthController;
use Framework\Core\Kernel;
use Framework\Health\HealthCheckRunner;
use Framework\Http\Request;
use Framework\Logger\LoggerInterface;
use Framework\Routing\Router;


/** @var Kernel $kernel */
$kernel = require dirname(__DIR__) . '/bootstrap/app.php';

$request = Request::fromGlobals();

$router = new Router($kernel->container);

// временно
$kernel->container->set(HealthController::class, fn ($c) => new HealthController(
    $c->get(HealthCheckRunner::class), $c->get(LoggerInterface::class),
));

$router->get('/health', [HealthController::class, 'index']);

$response = $router->dispatch($request);
$response->send();
