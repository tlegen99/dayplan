<?php

declare(strict_types=1);

namespace Framework\Database;

final readonly class Migrator
{
    public function __construct(
        private Connection $connection,
        private string $migrationDir
    ) {}

    public function migrate(): array
    {
        $this->ensureVersionsTable();
        $appliedMigrations = $this->connection->query("SELECT version FROM migration_versions");
        $migrationsFiles = glob($this->migrationDir . '/*.php');
        $appliedMigrationsVersions = array_flip(array_column($appliedMigrations, 'version'));

        $newMigrations = [];
        foreach ($migrationsFiles as $migrationFile) {
            $fileName = basename($migrationFile, '.php');

            if (isset($appliedMigrationsVersions[$fileName])) {
                continue;
            }

            $migration = require $migrationFile;
            $migrationUp = $migration['up'] ?? null;
            if ($migrationUp === null) {
                throw new \RuntimeException("Migration {$fileName} has no 'up' key");
            }

            $start = hrtime(true);

            $this->connection->transactional(function () use ($migrationUp, $fileName, $start) {
                $this->connection->executeRaw($migrationUp);

                $executionTime = (int)round((hrtime(true) - $start) / 1_000_000);
                $this->connection->execute(
                    'INSERT INTO migration_versions (version, execution_time) VALUES (:version, :execution_time)',
                    ['version' => $fileName, 'execution_time' => $executionTime]
                );
            });

            $newMigrations[] = $fileName;
        }

        return $newMigrations;
    }

    public function rollback(int $steps = 1): array
    {
        $this->ensureVersionsTable();
        $appliedMigrations = $this->connection->query(
            "SELECT version FROM migration_versions ORDER BY executed_at DESC, version DESC LIMIT :steps",
            ['steps' => $steps]
        );

        $rolledBackMigrations = [];
        foreach ($appliedMigrations as $row) {
            $fileName = $row['version'];
            $migrationFile = $this->migrationDir . '/' . $fileName . '.php';

            if (!file_exists($migrationFile)) {
                throw new \RuntimeException("Migration file for version {$fileName} not found!");
            }

            $migration = require $migrationFile;
            $migrationDown = $migration['down'] ?? null;
            if ($migrationDown === null) {
                throw new \RuntimeException("Migration {$fileName} has no 'down' key");
            }

            $this->connection->transactional(function () use ($migrationDown, $fileName) {
                $this->connection->executeRaw($migrationDown);

                $this->connection->execute(
                    'DELETE FROM migration_versions WHERE version = :version',
                    ['version' => $fileName]
                );
            });

            $rolledBackMigrations[] = $fileName;
        }

        return $rolledBackMigrations;
    }

    private function ensureVersionsTable(): void
    {
        $this->connection->executeRaw("CREATE TABLE IF NOT EXISTS migration_versions (
            version VARCHAR(255) NOT NULL,
            executed_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            execution_time INT DEFAULT NULL,
            PRIMARY KEY (version)
        )");
    }
}
