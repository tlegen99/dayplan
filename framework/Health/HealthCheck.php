<?php

declare(strict_types=1);

namespace Framework\Health;

interface HealthCheck
{
    public function name(): string;

    public function check(): void;
}
