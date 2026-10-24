<?php

declare(strict_types=1);

namespace Framework\Http;

final readonly class JsonResponse extends Response
{
    const int DEFAULT_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE;
    public function __construct(mixed $data = null, int $statusCode = 200, array $headers = [], int $flags = self::DEFAULT_FLAGS)
    {
        $jsonContent = json_encode($data, $flags);
        if ($jsonContent === false) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        $headers['content-type'] = 'application/json';

        parent::__construct($jsonContent, $statusCode, $headers);
    }
}
