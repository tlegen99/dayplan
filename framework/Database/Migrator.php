<?php

declare(strict_types=1);

namespace Framework\Database;

final class Migrator
{
    public function __construct(
        private Connection $connection,
        private string $migrationDir
    ) {}

    public function migrate(): array
    {
        $this->connection->executeRaw("CREATE TABLE IF NOT EXISTS migration_versions (
            version VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT now(),
            execution_time INT DEFAULT NULL,
            PRIMARY KEY (version)
        )");

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
            $start = hrtime(true);

            $this->connection->transactional(function () use ($migration, $fileName, $start) {
                $this->connection->executeRaw($migration['up']);

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

    public function rollback($steps): array
    {
        $appliedMigrations = $this->connection->query(
            "SELECT version FROM migration_versions ORDER BY version DESC LIMIT :steps",
            ['steps' => $steps]
        );

        $rolledBackMigrations = [];
        foreach ($appliedMigrations as $row) {
            $fileName = $row['version'];
            $migrationFile = $this->migrationDir . '/' . $fileName . '.php';

            if (!file_exists($migrationFile)) {
                throw new \Exception("Файл миграции для версии {$fileName} не найден!");
            }

            $migration = require $migrationFile;

            $this->connection->transactional(function () use ($migration, $fileName) {
                $this->connection->executeRaw($migration['down']);

                $this->connection->execute(
                    'DELETE FROM migration_versions WHERE version = :version',
                    ['version' => $fileName]
                );
            });

            $rolledBackMigrations[] = $fileName;
        }

        return $rolledBackMigrations;
    }
}
