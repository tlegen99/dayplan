<?php

namespace App\Task;

use DateTimeImmutable;

interface TaskRepository
{
    public function save(Task $task): void;

    public function findById(int $id): ?Task;

    public function findByDay(DateTimeImmutable $day): iterable;
}
