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

        $statusCode = $this->statusCodeFor($exception);
        $level = $statusCode < 500 ? 'warning' : 'critical';

        $logMessage = sprintf(
            "%s в файле %s на строке %d. Стек вызовов: %s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        try {
            $this->logger->log($logMessage, $level);
        } catch (\Throwable $logError) {
            error_log('Logger failed: ' . $logError->getMessage());
            error_log($logMessage);
        }

        if (ob_get_length()) {
            ob_clean();
        }

        if (headers_sent()) {
            return;
        }

        try {
            $response = $this->createResponse($exception, $statusCode);
        } catch (\Throwable) {
            $response = new Response('{"success":false,"error":{"message":"internal error"}}', 500, ['content-type' => 'application/json']);
        }

        $response->send();
    }

    private function statusCodeFor(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->statusCode();
        }

        $statusCode = 500;

        if ($exception instanceof DatabaseUnavailableException
            || $exception instanceof RedisUnavailableException
        ) {
            $statusCode = 503;
        }

        return $statusCode;
    }

    private function createResponse(Throwable $exception, int $statusCode): JsonResponse
    {
        if ($statusCode < 500) {
            $message = $exception->getMessage();
        } else {
            $message = $statusCode === 503 ? 'service unavailable' : 'internal error';
        }

        $response = [
            'success' => false,
            'error' => [
                'message' => $message
            ]
        ];

        if ($this->isDebug) {
            $response['error']['message']   = $exception->getMessage();
            $response['error']['exception'] = $exception::class;
            $response['error']['file'] = $exception->getFile();
            $response['error']['line'] = $exception->getLine();
            $response['error']['trace'] = explode("\n", $exception->getTraceAsString());
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
        if ($this->isDebug) {
            $flags = $flags | JSON_PRETTY_PRINT;
        }

        return new JsonResponse($response, $statusCode, [], $flags);
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
