<?php

declare(strict_types=1);

namespace App\Task;

enum TaskStatus: string
{
    case PLANNED = 'planned';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Запланирована',
            self::DONE => 'Выполнена',
            self::CANCELLED => 'Отменена',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PLANNED => [self::DONE, self::CANCELLED],
            self::DONE => [self::PLANNED],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(TaskStatus $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
