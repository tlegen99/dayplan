<?php

declare(strict_types=1);

namespace App\Task;

use DateTimeImmutable;

final class InMemoryTaskRepository implements TaskRepository
{
    /** @var array<int, Task> */
    private array $tasks = [];
    private int $nextId = 1;

    public function save(Task $task): void
    {
        if ($task->id === null) {
            $task->assignId($this->nextId++);
        }

        $this->tasks[$task->id] = $task;
    }

    public function findById(int $id): ?Task
    {
        return $this->tasks[$id] ?? null;
    }

    public function findByDay(DateTimeImmutable $day): iterable
    {
        $dayTasks = array_filter(
            $this->tasks,
            fn(Task $task): bool => $task->day->format('Y-m-d') === $day->format('Y-m-d')
        );

        usort($dayTasks, function (Task $a, Task $b): int {
            $timeA = $a->timeSlot->start;
            $timeB = $b->timeSlot->start;

            if ($timeA === null && $timeB === null) {
                return $a->id <=> $b->id;
            }

            if ($timeA === null) {
                return -1;
            }

            if ($timeB === null) {
                return 1;
            }

            return ($timeA <=> $timeB) ?: ($a->id <=> $b->id);
        });

        return $dayTasks;
    }
}
