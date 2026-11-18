<?php

declare(strict_types=1);

namespace App\Task\Exception;

use App\Task\TaskStatus;

final class InvalidStatusTransitionException extends TaskException
{
    public static function fromStatuses(TaskStatus $from, TaskStatus $to): self
    {
        return new self(
            sprintf(
                'Недопустимый переход статуса задачи: нельзя перевести из «%s» в «%s»',
                $from->label(),
                $to->label()
            )
        );
    }

    public static function cannotMoveInStatus(TaskStatus $currentStatus): self
    {
        return new self(
            sprintf(
                'Переносить можно только запланированные задачи (текущий статус: «%s»)',
                $currentStatus->label()
            )
        );
    }
}
