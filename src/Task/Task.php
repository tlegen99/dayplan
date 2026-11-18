<?php

declare(strict_types=1);

namespace App\Task;

use App\Task\Exception\InvalidStatusTransitionException;
use App\Task\Exception\InvalidTitleException;
use DateTimeImmutable;

class Task
{
    private const int MAX_TITLE_LENGTH = 255;
    private(set) ?int $id = null;
    private(set) string $title;
    private(set) DateTimeImmutable $day;
    private(set) TimeSlot $timeSlot;
    private(set) ?string $description;
    private(set) TaskStatus $status = TaskStatus::PLANNED;
    private(set) ?DateTimeImmutable $completedAt = null;

    public function __construct(
        string $title,
        DateTimeImmutable $day,
        ?string $startsAt = null,
        ?string $endsAt = null,
        ?string $description = null,
    )
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidTitleException('Название задачи обязательно');
        }

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidTitleException(
                sprintf('Название задачи не должно превышать %d символов', self::MAX_TITLE_LENGTH)
            );
        }

        $this->title = $title;
        $this->setTimeTrimming($day);
        $this->timeSlot = new TimeSlot($startsAt, $endsAt);
        $this->description = $description;
    }

    public function complete(DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(TaskStatus::DONE);

        $this->status = TaskStatus::DONE;
        $this->completedAt = $at;
    }

    public function cancel(): void
    {
        $this->assertCanTransitionTo(TaskStatus::CANCELLED);

        $this->status = TaskStatus::CANCELLED;
    }

    public function reopen(): void
    {
        $this->assertCanTransitionTo(TaskStatus::PLANNED);

        $this->status = TaskStatus::PLANNED;
        $this->completedAt = null;
    }

    public function moveTo(DateTimeImmutable $at): void
    {
        if ($this->status !== TaskStatus::PLANNED) {
            throw InvalidStatusTransitionException::cannotMoveInStatus($this->status);
        }

        $this->setTimeTrimming($at);
    }

    private function assertCanTransitionTo(TaskStatus $status): void
    {
        if (!$this->status->canTransitionTo($status)) {
            throw InvalidStatusTransitionException::fromStatuses($this->status, $status);
        }
    }

    private function setTimeTrimming(DateTimeImmutable $at): void
    {
        $this->day = $at->setTime(0, 0);
    }
}
