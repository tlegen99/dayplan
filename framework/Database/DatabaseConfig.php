<?php

declare(strict_types=1);

namespace Framework\Database;

final readonly class DatabaseConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $user,
        #[\SensitiveParameter] public string $password,
        public int $connectTimeout = 3,
    ) {
        if (trim($this->host) === '') {
            throw new \InvalidArgumentException('Database host must not be empty');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw new \InvalidArgumentException("Database port must be 1-65535, got {$this->port}");
        }

        if ($this->connectTimeout <= 0) {
            throw new \InvalidArgumentException("Database connect timeout must be greater than 0");
        }
    }

    /**
     * @param array $env
     * @return self
     */
    public static function fromEnv(array $env): self
    {
        return new self(
            host: self::required($env, 'DB_HOST'),
            port: self::requiredInt($env, 'DB_PORT'),
            database: self::required($env, 'DB_NAME'),
            user: self::required($env, 'DB_USER'),
            password: $env['DB_PASSWORD'] ?? '',
        );
    }

    /**
     * @param array $env
     * @param string $key
     * @return string
     */
    private static function required(array $env, string $key): string
    {
        $value = $env[$key] ?? '';

        if ($value === '') {
            throw new \InvalidArgumentException("Missing env variable: $key");
        }

        return $value;
    }

    /**
     * @param array $env
     * @param string $key
     * @return int
     */
    private static function requiredInt(array $env, string $key): int
    {
        $value = self::required($env, $key);
        $filtered = filter_var($value, FILTER_VALIDATE_INT);

        if ($filtered === false) {
            throw new \InvalidArgumentException("Env variable $key must be an integer, got '$value'");
        }

        return $filtered;
    }

    public function dsn(): string
    {
        return "pgsql:host={$this->host};port={$this->port};dbname={$this->database};connect_timeout={$this->connectTimeout}";
    }
}
