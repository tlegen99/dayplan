<?php

declare(strict_types=1);

namespace App\Health;

use Framework\Health\HealthCheckRunner;
use Framework\Http\JsonResponse;
use Framework\Http\Request;
use Framework\Logger\LoggerInterface;

final readonly class HealthController
{
    public function __construct(
        private HealthCheckRunner $health,
        private LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $results = $this->health->checkAll();

        $allHealthy = true;
        $body = [];
        foreach ($results as $result) {
            $allHealthy = $allHealthy && $result->healthy;
            $body[$result->name] = [
                'status'      => $result->healthy ? 'ok' : 'fail',
                'duration_ms' => $result->durationMs,
            ];

            if (!$result->healthy) {
                $this->logger->log("Health check {$result->name} failed: {$result->error}", 'warning');
            }
        }

        return new JsonResponse($body, $allHealthy ? 200 : 503);
    }
}
