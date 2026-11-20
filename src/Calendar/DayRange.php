<?php

declare(strict_types=1);

namespace App\Calendar;

use DateMalformedStringException;
use DateTimeImmutable;
use DomainException;

readonly class DayRange
{
    private DateTimeImmutable $start;
    private DateTimeImmutable $end;

    public function __construct(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
    ) {
        $this->start = $start->setTime(0, 0, 0);
        $this->end = $end->setTime(0, 0, 0);

        if ($this->start > $this->end) {
            throw new DomainException('Дата начала не может быть позже даты окончания');
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function days(): \Generator
    {
        $currentStart = $this->start;
        while ($currentStart <= $this->end) {
            yield $currentStart;
            $currentStart = $currentStart->modify('+1 day');
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function weekForDay(DateTimeImmutable $day): self
    {
        $day = $day->setTime(0, 0);
        $daysFromMonday = (int)$day->format('N') - 1;

        $mondayDate = $day->modify("-{$daysFromMonday} days");
        $sundayDate = $mondayDate->modify('+6 days');

        return new self($mondayDate, $sundayDate);
    }
}
