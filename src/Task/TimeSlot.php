<?php

declare(strict_types=1);

namespace App\Task;

use App\Task\Exception\InvalidTimeSlotException;

final readonly class TimeSlot
{
    private const string TIME_PATTERN = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';

    public function __construct(
        public ?string $start,
        public ?string $end,
    ) {
        if ($this->start !== null) {
            $this->assertValidFormat($this->start, 'start');
        }

        if ($this->end !== null) {
            $this->assertValidFormat($this->end, 'end');
        }

        // если есть дата окончания то и должна быть время начала
        if ($this->start === null && $this->end !== null) {
            throw new InvalidTimeSlotException(
                'Необходимо указать и время начала, и время окончания'
            );
        }

        if ($this->start !== null && $this->end !== null && $this->end <= $this->start) {
            throw new InvalidTimeSlotException(
                sprintf('Время окончания (%s) должно быть позже времени начала (%s)', $this->end, $this->start)
            );
        }
    }

    private function assertValidFormat(string $time, string $field): void
    {
        if (preg_match(self::TIME_PATTERN, $time) !== 1) {
            throw new InvalidTimeSlotException(
                sprintf('Некорректный формат времени для %s: "%s". Ожидается HH:MM', $field, $time)
            );
        }
    }
}

