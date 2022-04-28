<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PositionGenerator;
use Sbooker\PersistentSequences\Algorithm;
use Sbooker\PersistentSequences\Algorithm\Increment;
use Sbooker\PersistentSequences\Sequence;
use Sbooker\PersistentSequences\SequenceGenerator;
use Sbooker\PersistentSequences\SequenceReader;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\TransactionManagerAware;

final class PublishEventTest extends TestCase
{
    /**
     * @dataProvider dbs
     */
    public function testCreate(string $db): void
    {
        $first = 3;
        $em = $this->setUpDbDeps($db, $this->getPositionGenerator(new Increment($first)));

        $entityId = Uuid::uuid4();
        $this->getTransactionManager()->transactional(fn() =>
            $this->getTransactionManager()->persist(new Entity($entityId))
        );

        /** @var array<PersistentEvent> $events */
        $events = $this->getEventStorage($em)->getByEntityIdAndPositions($entityId);
        $position = (int)$this->getSequenceReader($em)->last('seq');

        $expectedPosition = $first + 1;

        try {
            $this->assertEquals($expectedPosition, $position, "Expects position $expectedPosition, $position given.");
            $this->assertCount(1, $events);
            $this->assertEventAtPosition(Created::class, $expectedPosition, $events[0]);
        } catch (\Throwable $exception) {
            $this->fail($exception->getMessage());
        } finally {
            $this->tearDownDbDeps($em);
            $em->clear();
        }
    }

    /**
     * @dataProvider dbs
     */
    public function testCreateAndDoubleUpdate(string $db): void
    {
        $first = 1100;
        $em = $this->setUpDbDeps($db, $this->getPositionGenerator(new Increment($first)));

        $entityId = Uuid::uuid4();
        $this->getTransactionManager()->transactional(function () use ($entityId) {
            $entity = new Entity($entityId);
            $this->getTransactionManager()->persist($entity);
        });

        $this->getTransactionManager()->transactional(function () use ($entityId) {
            /** @var Entity $entity */
            $entity = $this->getTransactionManager()->getLocked(Entity::class, $entityId);
            $entity->update();
            $entity->update();
        });

        /** @var array<PersistentEvent> $events */
        $events = $this->getEventStorage($em)->getByEntityIdAndPositions($entityId);
        $position = (int)$this->getSequenceReader($em)->last('seq');
        $expectedPosition = $first + 3;

        try {
            $this->assertEquals($expectedPosition, $position, "Expects position $expectedPosition, $position given.");
            $this->assertCount(3, $events);
            $this->assertEventAtPosition(Created::class, $first + 1, $events[0]);
            $this->assertEventAtPosition(Updated::class, $expectedPosition - 1 , $events[1]);
            $this->assertEventAtPosition(Updated::class, $expectedPosition, $events[2]);
        } catch (\Throwable $exception) {
            $this->fail($exception->getMessage());
        } finally {
            $this->tearDownDbDeps($em);
            $em->clear();
        }
    }

    private function assertEventAtPosition(string $expectedEventClass, int $expectedEventPosition, PersistentEvent $event): void
    {
        $this->assertEquals($this->getNameGiver()->getNameByClass($expectedEventClass), $event->getName());
        $this->assertEquals($expectedEventPosition, $event->getPosition());
    }


    private function getPositionGenerator(Algorithm $algorithm): PositionGenerator
    {
        return new class ($algorithm)  implements PositionGenerator, TransactionManagerAware {
            private ?SequenceGenerator $generator = null;
            private Algorithm $algorithm;

            public function __construct(Algorithm $algorithm)
            {
                $this->algorithm = $algorithm;
            }

            public function next(): int
            {
                return (int)$this->generator->next('seq', $this->algorithm);
            }

            public function setTransactionManager(TransactionManager $transactionManager): void
            {
                $this->generator = new SequenceGenerator($transactionManager);
            }
        };
    }

    private function getSequenceReader(EntityManager $em): SequenceReader
    {
        return new SequenceReader($em->getRepository(Sequence::class));
    }
}