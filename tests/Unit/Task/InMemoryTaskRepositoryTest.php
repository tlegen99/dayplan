<?php

declare(strict_types=1);

namespace App\Tests\Unit\Task;

use App\Task\InMemoryTaskRepository;
use App\Task\Task;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InMemoryTaskRepositoryTest extends TestCase
{
    private InMemoryTaskRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryTaskRepository();
    }

    public function testAssignsIdOneToFirstSavedTask(): void
    {
        $task = $this->task('Первая');
        $this->assertNull($task->id);

        $this->repo->save($task);

        $this->assertSame(1, $task->id);
    }

    public function testAssignsIdsInOrder(): void
    {
        $first = $this->task('Первая');
        $second = $this->task('Вторая');

        $this->repo->save($first);
        $this->repo->save($second);

        $this->assertSame(1, $first->id);
        $this->assertSame(2, $second->id);
    }

    public function testFindByIdReturnsSameTask(): void
    {
        $first = $this->task('Первая');
        $second = $this->task('Вторая');
        $this->repo->save($first);
        $this->repo->save($second);

        $this->assertSame($first, $this->repo->findById(1));
        $this->assertSame($second, $this->repo->findById(2));
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        $this->repo->save($this->task('Первая'));

        $this->assertNull($this->repo->findById(999));
    }

    public function testSavingExistingTaskKeepsIdAndDoesNotDuplicate(): void
    {
        $task = $this->task('Первая');
        $this->repo->save($task);

        $task->complete(new DateTimeImmutable('2026-11-20 18:00:00'));
        $this->repo->save($task);

        $this->assertSame(1, $task->id);
        $this->assertSame($task, $this->repo->findById(1));
        $this->assertNull($this->repo->findById(2));
        $this->assertCount(1, $this->toArray($this->repo->findByDay(new DateTimeImmutable('2026-11-20'))));
    }

    public function testFindByDayReturnsOnlyTasksOfThatDay(): void
    {
        $this->repo->save($this->task('20-е утро', '2026-11-20', '09:00', '10:00'));
        $this->repo->save($this->task('21-е', '2026-11-21'));
        $this->repo->save($this->task('20-е без времени', '2026-11-20'));

        $result = $this->repo->findByDay(new DateTimeImmutable('2026-11-20 15:30:00'));

        $this->assertSame(['20-е без времени', '20-е утро'], $this->titles($result));
    }

    public function testFindByDayReturnsEmptyArrayForEmptyDay(): void
    {
        $this->repo->save($this->task('20-е', '2026-11-20'));

        $result = $this->repo->findByDay(new DateTimeImmutable('2026-11-25'));

        $this->assertSame([], $this->toArray($result));
    }

    public function testFindByDayPutsTasksWithoutTimeFirstThenSortsByStart(): void
    {
        $this->repo->save($this->task('10:00', '2026-11-20', '10:00', '11:00'));
        $this->repo->save($this->task('Без времени', '2026-11-20'));
        $this->repo->save($this->task('09:00', '2026-11-20', '09:00', '09:30'));

        $result = $this->repo->findByDay(new DateTimeImmutable('2026-11-20'));

        $this->assertSame(['Без времени', '09:00', '10:00'], $this->titles($result));
    }

    public function testFindByDaySortsByIdWhenStartIsEqual(): void
    {
        $this->repo->save($this->task('09:00 id1', '2026-11-20', '09:00', '10:00'));
        $this->repo->save($this->task('Без времени id2', '2026-11-20'));
        $this->repo->save($this->task('09:00 id3', '2026-11-20', '09:00', '09:30'));
        $this->repo->save($this->task('Без времени id4', '2026-11-20'));

        $result = $this->repo->findByDay(new DateTimeImmutable('2026-11-20'));

        $this->assertSame(
            ['Без времени id2', 'Без времени id4', '09:00 id1', '09:00 id3'],
            $this->titles($result),
        );
    }

    private function task(
        string $title,
        string $day = '2026-11-20',
        ?string $startsAt = null,
        ?string $endsAt = null,
    ): Task {
        return new Task($title, new DateTimeImmutable($day), $startsAt, $endsAt);
    }

    /** @return list<Task> */
    private function toArray(iterable $tasks): array
    {
        return is_array($tasks) ? array_values($tasks) : iterator_to_array($tasks, false);
    }

    /** @return list<string> */
    private function titles(iterable $tasks): array
    {
        return array_map(fn(Task $task): string => $task->title, $this->toArray($tasks));
    }
}
