<?php

declare(strict_types=1);

namespace Framework\Logger;

final readonly class FileLogger implements LoggerInterface
{
    public function __construct(private string $logFile) {}

    public function log(string $message, string $level = 'error'): void
    {
        $date = date('Y-m-d H:i:s');
        $formatMessage = "[$date] [$level]: $message\n";
        file_put_contents($this->logFile, $formatMessage, FILE_APPEND);
    }
}
