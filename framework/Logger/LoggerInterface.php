<?php

declare(strict_types=1);

namespace Framework\Logger;
interface LoggerInterface
{
    public function log(string $message, string $level): void;
}
