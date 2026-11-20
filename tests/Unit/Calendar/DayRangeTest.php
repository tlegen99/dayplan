<?php

declare(strict_types=1);

namespace App\Tests\Unit\Calendar;

use App\Calendar\DayRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DayRangeTest extends TestCase
{
    public function testDays()
    {
        $range = new DayRange(
            new DateTimeImmutable('2026-11-16'),
            new DateTimeImmutable('2026-11-22'),
        );

        $days = iterator_to_array($range->days(), false);

        $this->assertCount(7, $days);
        $this->assertSame('2026-11-16', $days[0]->format('Y-m-d'));
        $this->assertSame('2026-11-22', $days[6]->format('Y-m-d'));
    }

    public function testOneDay()
    {
        $range = new DayRange(
            new DateTimeImmutable('2026-11-16'),
            new DateTimeImmutable('2026-11-16'),
        );

        $days = iterator_to_array($range->days(), false);

        $this->assertSame(1, count($days));
        $this->assertSame('2026-11-16', $days[0]->format('Y-m-d'));
    }

    public function testEndLessStart()
    {
        $this->expectException(\DomainException::class);
        new DayRange(
            new DateTimeImmutable('2026-11-16'),
            new DateTimeImmutable('2026-11-15'),
        );
    }

    public function testWeekForFriday()
    {
        $rangeWeek = DayRange::weekForDay(new DateTimeImmutable('2026-11-20'));

        $days = iterator_to_array($rangeWeek->days(), false);

        $this->assertCount(7, $days);
        $this->assertSame('2026-11-16', $days[0]->format('Y-m-d'));
        $this->assertSame('2026-11-22', $days[6]->format('Y-m-d'));
    }

    public function testWeekForMonday()
    {
        $rangeWeek = DayRange::weekForDay(new DateTimeImmutable('2026-11-16'));

        $days = iterator_to_array($rangeWeek->days(), false);

        $this->assertCount(7, $days);
        $this->assertSame('2026-11-16', $days[0]->format('Y-m-d'));
        $this->assertSame('2026-11-22', $days[6]->format('Y-m-d'));
    }

    public function testWeekForSunday()
    {
        $rangeWeek = DayRange::weekForDay(new DateTimeImmutable('2026-11-22'));

        $days = iterator_to_array($rangeWeek->days(), false);

        $this->assertCount(7, $days);
        $this->assertSame('2026-11-16', $days[0]->format('Y-m-d'));
        $this->assertSame('2026-11-22', $days[6]->format('Y-m-d'));
    }
}
