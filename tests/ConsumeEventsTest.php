<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\PersistentPointer\Pointer;
use Sbooker\PersistentPointer\Repository;

class ConsumeEventsTest extends TestCase
{
    /**
     * @dataProvider dbs
     */
    public function testTwoEventsSingleConsumer(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $event = new TestEvent(Uuid::uuid4(), "Some value");
        $otherEvent = new OtherEvent(Uuid::uuid4(), 1234, new Actor(Uuid::uuid4()));
        $consumer = $this->buildConsumer($em, "consumer", [TestEvent::class, OtherEvent::class], [$event, $otherEvent]);

        $this->publish($event);
        $this->publish($otherEvent);
        $firstConsume = $consumer->consume();
        $secondConsume = $consumer->consume();
        $thirdConsume = $consumer->consume();

        $this->assertTrue($firstConsume);
        $this->assertTrue($secondConsume);
        $this->assertFalse($thirdConsume);

        $this->tearDownDbDeps($em);
    }

    /**
     * @dataProvider dbs
     */
    public function testTwoEventsTwoConsumers(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $event = new TestEvent(Uuid::uuid4(), "Some value");
        $otherEvent = new OtherEvent(Uuid::uuid4(), 1234, new Actor(Uuid::uuid4()));
        $firstConsumer = $this->buildConsumer($em, "first.consumer", [TestEvent::class], [$event]);
        $secondConsumer = $this->buildConsumer($em, "second.consumer", [OtherEvent::class], [$otherEvent]);

        $this->publish($event);
        $this->publish($otherEvent);
        $firstConsume = $firstConsumer->consume();
        $secondConsume = $secondConsumer->consume();;
        $thirdConsume = $firstConsumer->consume();
        $fourthConsume = $secondConsumer->consume();

        $this->assertTrue($firstConsume);
        $this->assertTrue($secondConsume);
        $this->assertFalse($thirdConsume);
        $this->assertFalse($fourthConsume);

        $this->tearDownDbDeps($em);
    }

    /**
     * @dataProvider dbs
     */
    public function testConsumeSameEvents(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $event = new TestEvent(Uuid::uuid4(), "Some value");
        $otherEvent = new OtherEvent(Uuid::uuid4(), 1234, new Actor(Uuid::uuid4()));
        $firstConsumer = $this->buildConsumer($em,"first.consumer", [TestEvent::class, OtherEvent::class], [$event, $otherEvent]);
        $secondConsumer = $this->buildConsumer($em,"second.consumer", [OtherEvent::class], [$otherEvent]);

        $this->publish($event);
        $this->publish($otherEvent);
        $firstConsumerFirstConsume = $firstConsumer->consume();
        $secondConsumerFirstConsume = $secondConsumer->consume();
        $firstConsumerSecondConsume = $firstConsumer->consume();
        $secondConsumerSecondConsume = $secondConsumer->consume();;
        $firstConsumerThirdConsume = $firstConsumer->consume();
        $secondConsumerThirdConsume = $secondConsumer->consume();;

        $this->assertTrue($firstConsumerFirstConsume);
        $this->assertTrue($firstConsumerSecondConsume);
        $this->assertFalse($firstConsumerThirdConsume);
        $this->assertTrue($secondConsumerFirstConsume);
        $this->assertFalse($secondConsumerSecondConsume);
        $this->assertFalse($secondConsumerThirdConsume);

        $this->tearDownDbDeps($em);
    }

    final protected function buildConsumer(EntityManager $em,  string $name, array $eventClasses, array $expectedEvents): Consumer
    {
        return
            new Consumer(
                $this->getEventStorage($em),
                $this->getTransactionManager(),
                $this->getSerializer(),
                new Repository($em->getRepository(Pointer::class)),
                $this->getNameGiver(),
                $this->buildSubscriber($eventClasses, $expectedEvents),
                $name
            );
    }

    private function buildSubscriber(array $eventClasses, array $expectedEvents): DomainEventSubscriber
    {
        $mock = $this->createMock(DomainEventSubscriber::class);
        $mock->method('getListenedEventClasses')->willReturn($eventClasses);
        $mock->expects($this->exactly(count($expectedEvents)))->method('handleEvent')
            ->withConsecutive(
                ... array_map(fn(DomainEvent $event) => [$event], $expectedEvents)
            );

        return $mock;
    }
}

