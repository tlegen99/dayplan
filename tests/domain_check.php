<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Task\Exception\InvalidStatusTransitionException;
use App\Task\Exception\InvalidTimeSlotException;
use App\Task\Exception\InvalidTitleException;
use App\Task\Task;
use App\Task\TaskStatus;

function check(string $name, mixed $actual, mixed $expected): void
{
    $ok = $actual === $expected;
    $status = $ok ? "\033[32mOK - \033[0m" : "\033[31mFAIL - \033[0m";

    echo $status . $name
        . ($ok ? '' : ' — ожидал ' . var_export($expected, true) . ', получил ' . var_export($actual, true))
        . PHP_EOL;
}

function checkThrows(string $name, callable $fn, string $exceptionClass): void
{
    try {
        $fn();
        echo "\033[31mFAIL - \033[0m" . $name . ' — исключения не было' . PHP_EOL;
    } catch (Throwable $e) {
        $ok = $e instanceof $exceptionClass;
        $status = $ok ? "\033[32mOK - \033[0m" : "\033[31mFAIL - \033[0m";

        echo $status . $name
            . ($ok ? '' : ' — ожидал ' . $exceptionClass . ', получил ' . $e::class)
            . PHP_EOL;
    }
}

function checkNoThrow(string $name, callable $fn): void
{
    try {
        $fn();
        echo "\033[32mOK - \033[0m" . $name . PHP_EOL;
    } catch (Throwable $e) {
        echo "\033[31mFAIL - \033[0m" . $name . ' — исключение ' . $e::class . ': ' . $e->getMessage() . PHP_EOL;
    }
}

check(
    "название ' Купить хлеб '",
    new Task(' Купить хлеб ', new DateTimeImmutable('2026-11-17 12:00:00'))->title,
    'Купить хлеб'
);

checkThrows('пустое название', function () {
    new Task('', new DateTimeImmutable('2026-11-17 12:00:00'));
}, InvalidTitleException::class);

checkThrows('256 символов', function () {
    new Task(str_repeat('F', 256), new DateTimeImmutable('2026-11-17 12:00:00'));
}, InvalidTitleException::class);

// время '09:30'; '24:00'; '9:5x'	создаётся; исключение; исключение
checkNoThrow("время '09:30'", function () {
    new Task('test 09:30', new DateTimeImmutable('2026-11-17 12:00:00'), '09:30');
});
checkThrows("время '24:00'", function () {
    new Task('test 24:00', new DateTimeImmutable('2026-11-17 12:00:00'), '24:00');
}, InvalidTimeSlotException::class);
checkThrows("время '9:5x'", function () {
    new Task('test 9:5x', new DateTimeImmutable('2026-11-17 12:00:00'), '9:5x');
}, InvalidTimeSlotException::class);

checkThrows("10:00–09:00", function () {
    new Task('10:00–09:00', new DateTimeImmutable('2026-11-17 12:00:00'), '10:00', '09:00');
}, InvalidTimeSlotException::class);
checkThrows("10:00–10:00", function () {
    new Task('10:00–10:00', new DateTimeImmutable('2026-11-17 12:00:00'), '10:00', '10:00');
}, InvalidTimeSlotException::class);

checkNoThrow("без начала и конца", function () {
    new Task('без начала и конца', new DateTimeImmutable('2026-11-17 12:00:00'));
});
checkNoThrow("только начало", function () {
    new Task('только начало', new DateTimeImmutable('2026-11-17 12:00:00'), '10:00');
});


$newTask = new Task('Новая задача', new DateTimeImmutable('2026-11-17 12:00:00'));
check('новая задача: статус запланирована', $newTask->status, TaskStatus::PLANNED);
check('новая задача: даты выполнения нет', $newTask->completedAt, null);

$taskWithTime = new Task('Задача на день', new DateTimeImmutable('2026-11-18 15:00:00'));
check(
    'день 2026-11-18 15:00: задача на 2026-11-18',
    $taskWithTime->day->format('Y-m-d'),
    '2026-11-18'
);

$taskToComplete = new Task('Выполнить задачу', new DateTimeImmutable('2026-11-17 12:00:00'));
$completedAt = new DateTimeImmutable('2026-11-17 18:30:00');
$taskToComplete->complete($completedAt);
check('выполнить: статус «выполнена»', $taskToComplete->status, TaskStatus::DONE);
check('выполнить: дата выполнения та, что передали', $taskToComplete->completedAt, $completedAt);

checkThrows('выполнить ещё раз', function () use ($taskToComplete) {
    $taskToComplete->complete(new DateTimeImmutable('2026-11-17 19:00:00'));
}, InvalidStatusTransitionException::class);

$taskToCancel = new Task('Отменить задачу', new DateTimeImmutable('2026-11-17 12:00:00'));
$taskToCancel->cancel();
check('отменить запланированную: статус «отменена»', $taskToCancel->status, TaskStatus::CANCELLED);
checkThrows('выполнить отменённую', function () use ($taskToCancel) {
    $taskToCancel->complete(new DateTimeImmutable('2026-11-17 14:00:00'));
}, InvalidStatusTransitionException::class);

$taskToReopen = new Task('Вернуть в работу', new DateTimeImmutable('2026-11-17 12:00:00'));
$taskToReopen->complete(new DateTimeImmutable('2026-11-17 15:00:00'));
$taskToReopen->reopen();
check('вернуть выполненную в работу: статус «запланирована»', $taskToReopen->status, TaskStatus::PLANNED);
check('вернуть выполненную в работу: даты выполнения нет', $taskToReopen->completedAt, null);

$taskToReschedule = new Task('Перенести задачу', new DateTimeImmutable('2026-11-17 12:00:00'));
$newDate = new DateTimeImmutable('2026-11-20 00:00:00');
$taskToReschedule->moveTo($newDate);
check(
    'перенести запланированную: день сменился',
    $taskToReschedule->day->format('Y-m-d'),
    '2026-11-20'
);

checkThrows('перенести выполненную', function () use ($taskToComplete) {
    $taskToComplete->moveTo(new DateTimeImmutable('2026-11-20 00:00:00'));
}, InvalidStatusTransitionException::class);
