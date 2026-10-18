<?php

declare(strict_types=1);

namespace Framework\Http;

use ErrorException;
use Framework\Database\DatabaseUnavailableException;
use Framework\Logger\LoggerInterface;
use Framework\Redis\RedisUnavailableException;
use Throwable;

final readonly class ErrorHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private bool            $isDebug = false
    ) {}

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * @throws ErrorException
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(Throwable $exception): void
    {
        restore_error_handler();

        $logMessage = sprintf(
            "%s в файле %s на строке %d. Стек вызовов: %s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        try {
            $this->logger->log($logMessage, 'critical');
        } catch (\Throwable $logError) {
            error_log('Logger failed: ' . $logError->getMessage());
            error_log($logMessage);
        }

        $this->sendJsonResponder($exception);
    }

    private function sendJsonResponder(Throwable $exception): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        $isUnavailable = $exception instanceof DatabaseUnavailableException
            || $exception instanceof RedisUnavailableException;

        $statusCode = $isUnavailable ? 503 : 500;

        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        $publicMessage = $statusCode === 503 ? 'service unavailable' : 'internal error';

        $response = [
            'success' => false,
            'error' => [
                'message' => $publicMessage
            ]
        ];

        if ($this->isDebug) {
            $response['error']['message']   = $exception->getMessage();
            $response['error']['exception'] = $exception::class;
            $response['error']['file'] = $exception->getFile();
            $response['error']['line'] = $exception->getLine();
            $response['error']['trace'] = explode("\n", $exception->getTraceAsString());
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $this->handleException(new \ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        ));
    }
}
