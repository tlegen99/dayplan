<?php

declare(strict_types=1);

namespace Framework\Health;

final readonly class HealthResult
{
    private function __construct(
        public string $name,
        public bool $healthy,
        public float $durationMs,
        public ?string $error = null,
    ) {}

    public static function healthy(string $name, float $durationMs): self
    {
        return new self($name, true, $durationMs);
    }

    public static function unhealthy(string $name, float $durationMs, string $error): self
    {
        return new self($name, false, $durationMs, $error);
    }
}
