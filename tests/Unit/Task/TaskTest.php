<?php

declare(strict_types=1);

namespace App\Tests\Unit\Task;

use App\Task\Exception\InvalidStatusTransitionException;
use App\Task\Exception\InvalidTitleException;
use App\Task\Task;
use App\Task\TaskStatus;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function testTrimsTitle(): void
    {
        $task = new Task(' Купить хлеб ', new DateTimeImmutable('2026-11-17 12:00:00'));

        $this->assertSame('Купить хлеб', $task->title);
    }

    public function testThrowsExceptionWhenTitleIsEmpty(): void
    {
        $this->expectException(InvalidTitleException::class);

        new Task(' ', new DateTimeImmutable('2026-11-17 12:00:00'));
    }

    public function testAcceptsTitleOf255Symbols(): void
    {
        $title = str_repeat('F', 255);
        $task = new Task($title, new DateTimeImmutable('2026-11-17 12:00:00'));

        $this->assertSame($title, $task->title);
    }

    public function testThrowsExceptionWhenTitleIs256Symbols(): void
    {
        $this->expectException(InvalidTitleException::class);

        new Task(str_repeat('F', 256), new DateTimeImmutable('2026-11-17 12:00:00'));
    }

    public function testSetsTimeSlot(): void
    {
        $task = new Task('Встреча', new DateTimeImmutable('2026-11-17 12:00:00'), '09:30', '10:30');

        $this->assertSame('09:30', $task->timeSlot->start);
        $this->assertSame('10:30', $task->timeSlot->end);
    }

    public function testNewTaskHasPlannedStatusAndNoCompletedDate(): void
    {
        $task = new Task('Новая задача', new DateTimeImmutable('2026-11-17 12:00:00'));

        $this->assertSame(TaskStatus::PLANNED, $task->status);
        $this->assertNull($task->completedAt);
    }

    public function testSetsDayWithoutTimeComponent(): void
    {
        $task = new Task('Задача на день', new DateTimeImmutable('2026-11-18 15:00:00'));

        $this->assertSame('2026-11-18', $task->day->format('Y-m-d'));
    }

    public function testCompletesPlannedTask(): void
    {
        $task = new Task('Выполнить задачу', new DateTimeImmutable('2026-11-17 12:00:00'));
        $completedAt = new DateTimeImmutable('2026-11-17 18:30:00');

        $task->complete($completedAt);

        $this->assertSame(TaskStatus::DONE, $task->status);
        $this->assertSame($completedAt, $task->completedAt);
    }

    public function testThrowsExceptionWhenCompletingAlreadyCompletedTask(): void
    {
        $task = new Task('Выполнить задачу', new DateTimeImmutable('2026-11-17 12:00:00'));
        $task->complete(new DateTimeImmutable('2026-11-17 18:30:00'));

        $this->expectException(InvalidStatusTransitionException::class);

        $task->complete(new DateTimeImmutable('2026-11-17 19:00:00'));
    }

    public function testCancelsPlannedTask(): void
    {
        $task = new Task('Отменить задачу', new DateTimeImmutable('2026-11-17 12:00:00'));

        $task->cancel();

        $this->assertSame(TaskStatus::CANCELLED, $task->status);
    }

    public function testThrowsExceptionWhenCompletingCancelledTask(): void
    {
        $task = new Task('Отменить задачу', new DateTimeImmutable('2026-11-17 12:00:00'));
        $task->cancel();

        $this->expectException(InvalidStatusTransitionException::class);

        $task->complete(new DateTimeImmutable('2026-11-17 14:00:00'));
    }

    public function testReopensCompletedTask(): void
    {
        $task = new Task('Вернуть в работу', new DateTimeImmutable('2026-11-17 12:00:00'));
        $task->complete(new DateTimeImmutable('2026-11-17 15:00:00'));

        $task->reopen();

        $this->assertSame(TaskStatus::PLANNED, $task->status);
        $this->assertNull($task->completedAt);
    }

    public function testMovesPlannedTaskToAnotherDay(): void
    {
        $task = new Task('Перенести задачу', new DateTimeImmutable('2026-11-17 12:00:00'));

        $task->moveTo(new DateTimeImmutable('2026-11-20 00:00:00'));

        $this->assertSame('2026-11-20', $task->day->format('Y-m-d'));
    }

    public function testCannotMoveCompletedTask(): void
    {
        $task = new Task('Перенос выполненной', new DateTimeImmutable('2026-11-17 00:00:00'));
        $task->complete(new DateTimeImmutable('2026-11-18 14:00:00'));

        $this->expectException(InvalidStatusTransitionException::class);

        $task->moveTo(new DateTimeImmutable('2026-11-20 00:00:00'));
    }

    public function testCannotAssignIdTwice(): void
    {
        $task = new Task('Купить хлеб', new DateTimeImmutable('2026-11-20'));
        $task->assignId(1);

        $this->expectException(LogicException::class);

        $task->assignId(2);
    }
}
