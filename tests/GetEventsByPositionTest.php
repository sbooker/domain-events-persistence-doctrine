<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Persistence\PositionGenerator;

final class GetEventsByPositionTest extends TestCase
{
    /**
     * @dataProvider examples
     */
    public function test(string $db, array $positions, ?int $from, ?int $to, bool $ordering, array $expected): void
    {
        $entityId = Uuid::uuid4();
        $this->setPositionGenerator($this->createPositionGenerator($positions));
        $em = $this->setUpDbDeps($db);
        $this->publishEvent($entityId);
        $this->publishEvent($entityId);
        $this->publishEvent($entityId);

        $events = $this->getEventStorage($em)->getByEntityIdAndPositions($entityId, $from, $to, $ordering);

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
            [[ 1, 2, 3 ], 1, 3, true, [2]],
            [[ 1, 2, 3 ], null, 3, true,  [1, 2]],
            [[ 1, 2, 3 ], 1, null, false, [3 ,2]],
            [[ 1, 2, 3 ], null, null, true, [1, 2, 3]],
            [[ 1, 2, 3 ], null, null, false, [3, 2, 1]],
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
    private function createPositionGenerator(array $positions): PositionGenerator
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
                $this->position++;

                return $current;
            }
        };
    }

    private function publishEvent(UuidInterface $entityId): void
    {
        $this->publish(new TestEvent($entityId, 'abracadabra'));
    }
}

