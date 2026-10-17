<?php

declare(strict_types=1);

namespace Framework\Database;

use PDOException;

final class DatabaseUnavailableException extends \RuntimeException
{
    public static function fromPdoException(PDOException $e, DatabaseConfig $config): self
    {
        return new self("Failed to connect to PostgreSQL at {$config->host}:{$config->port}", previous: $e);
    }
}
