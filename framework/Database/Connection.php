<?php

declare(strict_types=1);

namespace Framework\Database;

use PDO;

final class Connection
{
    private ?PDO $pdo = null;
    private int $transactionNestingLevel = 0;

    public function __construct(
        private readonly DatabaseConfig $dbConfig
    ) {}

    /**
     * @param array<string|int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function query(string $sql, array $params = []): array
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function executeRaw(string $sql): void
    {
        $this->pdo()->exec($sql);
    }

    /**
     * @throws \Throwable
     */
    public function transactional(callable $fn): mixed
    {
        $this->transactionNestingLevel++;

        if ($this->transactionNestingLevel === 1) {
            $this->pdo()->beginTransaction();
        } else {
            $savepointName = 'LEVEL_' . $this->transactionNestingLevel;
            $this->pdo()->exec("SAVEPOINT {$savepointName}");
        }

        try {
            $result = $fn();

            if ($this->transactionNestingLevel > 1) {
                $savepointName = 'LEVEL_' . $this->transactionNestingLevel;
                $this->pdo()->exec("RELEASE SAVEPOINT {$savepointName}");
            } else {
                $this->pdo()->commit();
            }

            $this->transactionNestingLevel--;
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo()->inTransaction()) {
                if ($this->transactionNestingLevel > 1) {
                    $savepointName = 'LEVEL_' . $this->transactionNestingLevel;
                    $this->pdo()->exec("ROLLBACK TO SAVEPOINT {$savepointName}");
                } else {
                    // откатываем всё если упало на 1 уровне
                    $this->pdo()->rollBack();
                }
            }

            $this->transactionNestingLevel--;
            throw $e;
        }
    }

    private function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $this->pdo = new PDO(
                $this->dbConfig->dsn(),
                $this->dbConfig->user,
                $this->dbConfig->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            return $this->pdo;
        } catch (\PDOException $e) {
            throw DatabaseUnavailableException::fromPdoException($e, $this->dbConfig);
        }
    }
}

