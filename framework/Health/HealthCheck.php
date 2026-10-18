<?php

declare(strict_types=1);

namespace Framework\Health;

interface HealthCheck
{
    public function check(): HealthResult;
}
