<?php

declare(strict_types=1);

namespace Framework\Http;

use JsonException;

final readonly class Request
{
    public function __construct(
        private string $method,
        private string $path,
        private array  $query,
        private array  $body,
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
        $getData = $_GET;
        $postData = $_POST;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        if (str_contains(strtolower($contentType), 'application/json')) {
            $rawBody = file_get_contents('php://input') ?: '';

            if ($rawBody === '') {
                $postData = [];
            } else {
                try {
                    $jsonData = json_decode($rawBody, false, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new HttpException(400, "Invalid json in body", $e);
                }

                // $jsonData ожидается именно в виде объекта а не [1,2,3]
                if (!is_object($jsonData)) {
                    throw new HttpException(400, "JSON body must be an object");
                }

                $postData = json_decode($rawBody, true);
            }
        }

        return new self($method, $path, $getData, $postData);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }
}
