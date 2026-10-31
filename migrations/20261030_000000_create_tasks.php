<?php

return [
    'up' => <<<'SQL'
        CREATE TABLE tasks (
            id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            title        VARCHAR(255) NOT NULL,
            description  TEXT NULL,
            day          DATE NOT NULL,
            starts_at    TIME NULL,
            ends_at      TIME NULL,
            status       VARCHAR(20) NOT NULL DEFAULT 'planned',
            completed_at TIMESTAMPTZ NULL,
            created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
            updated_at   TIMESTAMPTZ NOT NULL DEFAULT now(),

            CONSTRAINT tasks_title_not_empty CHECK (title <> ''),
            CONSTRAINT tasks_status_valid    CHECK (status IN ('planned', 'done', 'cancelled')),
            CONSTRAINT tasks_time_order      CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at > starts_at)
        );

        CREATE INDEX tasks_day_starts_at_idx ON tasks (day, starts_at);
        SQL,
    'down' => 'DROP TABLE tasks',
];
