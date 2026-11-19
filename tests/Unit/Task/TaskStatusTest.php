<?php

declare(strict_types=1);

namespace App\Tests\Unit\Task;

use App\Task\TaskStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    #[DataProvider('transitionsProvider')]
    public function testStatusTransitions(TaskStatus $from, TaskStatus $to, bool $expectedAllowed): void
    {
        $this->assertSame($expectedAllowed, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{TaskStatus, TaskStatus, bool}>
     */
    public static function transitionsProvider(): iterable
    {
        // из PLANNED
        yield 'PLANNED -> PLANNED (запрещено)' => [TaskStatus::PLANNED, TaskStatus::PLANNED, false];
        yield 'PLANNED -> DONE (разрешено)' => [TaskStatus::PLANNED, TaskStatus::DONE, true];
        yield 'PLANNED -> CANCELLED (разрешено)' => [TaskStatus::PLANNED, TaskStatus::CANCELLED, true];

        // из DONE
        yield 'DONE -> PLANNED (разрешено / reopen)' => [TaskStatus::DONE, TaskStatus::PLANNED, true];
        yield 'DONE -> DONE (запрещено)' => [TaskStatus::DONE, TaskStatus::DONE, false];
        yield 'DONE -> CANCELLED (запрещено)' => [TaskStatus::DONE, TaskStatus::CANCELLED, false];

        // из CANCELLED
        yield 'CANCELLED -> PLANNED (запрещено)' => [TaskStatus::CANCELLED, TaskStatus::PLANNED, false];
        yield 'CANCELLED -> DONE (запрещено)' => [TaskStatus::CANCELLED, TaskStatus::DONE, false];
        yield 'CANCELLED -> CANCELLED (запрещено)' => [TaskStatus::CANCELLED, TaskStatus::CANCELLED, false];
    }
}
