<?php

declare(strict_types=1);

namespace Framework\Health;

final class HealthCheckRunner
{
    /** @param list<HealthCheck> $checks */
    public function __construct(
        private readonly array $checks,
    ) {}

    /** @return list<HealthResult> */
    public function checkAll(): array
    {
        return array_map(fn (HealthCheck $check): HealthResult => $this->run($check), $this->checks);
    }

    private function run(HealthCheck $check): HealthResult
    {
        $start = hrtime(true);

        try {
            $check->check();

            return HealthResult::healthy($check->name(), $this->elapsedMs($start));
        } catch (\Throwable $e) {
            return HealthResult::unhealthy($check->name(), $this->elapsedMs($start), $e->getMessage());
        }
    }

    private function elapsedMs(int $start): float
    {
        return round((hrtime(true) - $start) / 1_000_000, 2);
    }
}
