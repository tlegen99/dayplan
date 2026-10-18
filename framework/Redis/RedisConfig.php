<?php

declare(strict_types=1);

namespace Framework\Redis;

final readonly class RedisConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public float $connectTimeout = 1.0,
    ) {
        if (trim($this->host) === '') {
            throw new \InvalidArgumentException('Redis host must not be empty');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw new \InvalidArgumentException("Redis port must be 1-65535, got {$this->port}");
        }

        if ($this->connectTimeout <= 0) {
            throw new \InvalidArgumentException("Redis connect timeout must be greater than 0");
        }
    }

    /** @param array<string, string> $env */
    public static function fromEnv(array $env): self
    {
        return new self(
            host: self::required($env, 'REDIS_HOST'),
            port: self::requiredInt($env, 'REDIS_PORT'),
        );
    }

    /** @param array<string, string> $env */
    private static function required(array $env, string $key): string
    {
        $value = $env[$key] ?? '';

        if ($value === '') {
            throw new \InvalidArgumentException("Missing env variable: $key");
        }

        return $value;
    }

    /** @param array<string, string> $env */
    private static function requiredInt(array $env, string $key): int
    {
        $value = self::required($env, $key);
        $filtered = filter_var($value, FILTER_VALIDATE_INT);

        if ($filtered === false) {
            throw new \InvalidArgumentException("Env variable $key must be an integer, got '$value'");
        }

        return $filtered;
    }
}
