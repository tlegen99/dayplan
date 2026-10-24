<?php

declare(strict_types=1);

namespace Framework\Http;

readonly class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;
    const string CHARSET = 'UTF-8';

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        $headers['content-type'] ??= 'text/html; charset=' . self::CHARSET;

        if (
            str_contains($headers['content-type'], 'text/') &&
            !str_contains($headers['content-type'], 'charset')
        ) {
            $headers['content-type'] .= '; charset=' . self::CHARSET;
        }

        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function withHeader(string $name, string $value): static
    {
        $headers = $this->headers;
        $headers[strtolower($name)] = $value;

        return clone($this, ['headers' => $headers]);
    }

    public function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            $ucName = ucwords($name, '-');
            header("$ucName: $value");
        }

        return $this;
    }

    public function sendContent(): static
    {
        echo $this->content;

        return $this;
    }

    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
