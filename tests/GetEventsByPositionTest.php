<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Persistence\PositionGenerator;

final class GetEventsByPositionTest extends TestCase
{
    /**
     * @dataProvider examples
     */
    public function test(string $db, ?int $from, ?int $to, bool $ordering, array $expected): void
    {
        $em = $this->setUpDbDeps($db, $this->getPositionGenerator([ 1, 2, 3 ]));
        $entityId = Uuid::uuid4();
        $this->publishEvent($em, $entityId, '1');
        $this->publishEvent($em, $entityId, '2');
        $this->publishEvent($em, $entityId, '3');
        $em->flush();
        $em->clear();

        $events = $this->getEventStorage($em)->getByEntityIdAndPositions($entityId, $from, $to, $ordering);
        $control = $this->getEventStorage($em)->getByEntityIdAndPositions($entityId);

        $this->assertEquals(3, count($control));
        $this->assertEquals(count($expected), count($events));
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $events[$key]->getPosition());
        }


        $this->tearDownDbDeps($em);
    }

    public function examples(): array
    {
        return $this->mergeExamples($this->dbs(), $this->positionExamples());
    }

    private function positionExamples(): array
    {
        return [
            [ 1, 3, true, [2]],
            [ null, 3, true,  [1, 2]],
            [ 1, null, false, [3 ,2]],
            [ null, null, true, [1, 2, 3]],
            [ null, null, false, [3, 2, 1]],
        ];
    }

    private function mergeExamples(array $firstExamples, array $secondExamples): array
    {
        $merged = [];

        foreach ($firstExamples as $firstExamplesItem) {
            foreach ($secondExamples as $secondExampleItem) {
                $merged[] = array_merge($firstExamplesItem, $secondExampleItem);
            }
        }

        return $merged;
    }

    /**
     * @param array<int> $positions
     */
    private function getPositionGenerator(array $positions): PositionGenerator
    {
        return new class ($positions) implements PositionGenerator {
            private int $position = 0;
            private array $positions;

            public function __construct(array $positions)
            {
                $this->positions = $positions;
            }

            public function next(): int
            {
                $current = $this->positions[$this->position];
                $this->position+= 1;

                return $current;
            }
        };
    }

    private function publishEvent(EntityManagerInterface $em, UuidInterface $entityId, string $value): void
    {
        $this->publish(new TestEvent($entityId, $value));
    }
}

