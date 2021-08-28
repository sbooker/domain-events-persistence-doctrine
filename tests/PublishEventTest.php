<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Ramsey\Uuid\Uuid;

final class PublishEventTest extends TestCase
{
    /**
     * @dataProvider dbs
     */
    public function test(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $entityId = Uuid::uuid4();
        $this->getTransactionManager()->transactional(fn() =>
            $this->getTransactionManager()->persist(new Entity($entityId))
        );

        $events = $this->getEventStorage($em)->getByEntityIdAndPositions($entityId);

        try {
            $this->assertCount(1, $events);
        } catch (\Throwable $exception) {
            $this->fail($exception->getMessage());
        } finally {
            $this->tearDownDbDeps($em);
        }
    }
}