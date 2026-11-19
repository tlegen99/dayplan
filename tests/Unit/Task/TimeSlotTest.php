<?php

declare(strict_types=1);

namespace App\Tests\Unit\Task;

use App\Task\Exception\InvalidTimeSlotException;
use App\Task\TimeSlot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TimeSlotTest extends TestCase
{
    #[DataProvider('validTimeSlotProvider')]
    public function testCreatesValidTimeSlot(?string $start, ?string $end): void
    {
        $slot = new TimeSlot($start, $end);

        $this->assertSame($start, $slot->start);
        $this->assertSame($end, $slot->end);
    }

    #[DataProvider('invalidTimeSlotProvider')]
    public function testThrowsExceptionOnInvalidTimeSlot(?string $start, ?string $end): void
    {
        $this->expectException(InvalidTimeSlotException::class);

        new TimeSlot($start, $end);
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function validTimeSlotProvider(): iterable
    {
        yield 'без начала и конца' => [null, null];
        yield 'только начало' => ['09:30', null];
        yield 'начало и конец' => ['09:30', '11:00'];
        yield 'границы суток 00:00 - 23:59' => ['00:00', '23:59'];
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function invalidTimeSlotProvider(): iterable
    {
        yield 'час 24:00' => ['24:00', null];
        yield 'неверный формат 9:5x' => ['9:5x', null];
        yield 'без ведущего нуля 9:30' => ['9:30', null];
        yield 'конец меньше начала' => ['10:00', '09:00'];
        yield 'конец равен началу' => ['10:00', '10:00'];
        yield 'конец без начала' => [null, '10:00'];
    }
}
