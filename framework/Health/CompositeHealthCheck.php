<?php

declare(strict_types=1);

namespace Framework\Health;

final class CompositeHealthCheck
{
    /** @param list<HealthCheck> $checks */
    public function __construct(
        private readonly array $checks,
    ) {}

    /** @return list<HealthResult> */
    public function checkAll(): array
    {
        return array_map(
            static fn (HealthCheck $check): HealthResult => $check->check(),
            $this->checks,
        );
    }
}
