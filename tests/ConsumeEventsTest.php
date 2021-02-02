<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Gamez\Symfony\Component\Serializer\Normalizer\UuidNormalizer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\Persistence\EventNameGiver;
use Sbooker\DomainEvents\Persistence\EventStorage;
use Sbooker\DomainEvents\Persistence\MapNameGiver;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PersistentPublisher;
use Sbooker\PersistentPointer\Pointer;
use Sbooker\PersistentPointer\Repository;
use Sbooker\TransactionManager\DoctrineTransactionHandler;
use Sbooker\TransactionManager\TransactionManager;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class ConsumeEventsTest extends TestCase
{
    private const DATE_FORMAT = "Y-m-d\TH:i:s.uP";

    private SchemaTool $schemaTool;

    private EventNameGiver $nameGiver;

    private Serializer $serializer;

    private PersistentPublisher $publisher;

    private TransactionManager $transactionManager;

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

    public function dbs(): array
    {
        return [
            [ EntityManagerBuilder::PGSQL11 ],
            [ EntityManagerBuilder::PGSQL12 ],
            [ EntityManagerBuilder::MYSQL5 ],
            [ EntityManagerBuilder::MYSQL8 ],
        ];
    }

    private function publish(DomainEvent $event): void
    {
        $this->transactionManager->transactional(fn() => $this->publisher->publish($event));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->nameGiver = $this->buildNameGiver();
        $this->serializer = $this->buildSerializer();
    }

    private function setUpDbDeps(string $db): EntityManager
    {
        $em = EntityManagerBuilder::me()->get($db);
        $this->schemaTool = new SchemaTool($em);
        $this->publisher = new PersistentPublisher($this->getEventStorage($em), $this->nameGiver, $this->serializer);
        $this->transactionManager = new TransactionManager(new DoctrineTransactionHandler($em));
        $this->schemaTool->createSchema($this->getMetadata($em));

        return $em;
    }

    private function tearDownDbDeps(EntityManager $em): void
    {
        $this->schemaTool->dropSchema($this->getMetadata($em));
        $this->em = null;
    }

    private function buildConsumer(EntityManager $em,  string $name, array $eventClasses, array $expectedEvents): Consumer
    {
        return
            new Consumer(
                $this->getEventStorage($em),
                $this->transactionManager,
                $this->serializer,
                new Repository(
                    $em->getRepository(Pointer::class)
                ),
                $this->nameGiver,
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

    private function getEventStorage(EntityManager $em): EventStorage
    {
        return $em->getRepository(PersistentEvent::class);
    }

    private function getMetadata(EntityManager $em)
    {
        return $em->getMetadataFactory()->getAllMetadata();
    }

    private function buildSerializer(): Serializer
    {
        return new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT]),
            new UuidNormalizer(),
            new PropertyNormalizer(
                null,
                null,
                new PropertyInfoExtractor(
                    [],
                    [
                        new PhpDocExtractor(),
                        new ReflectionExtractor(),
                    ]
                )
            ),
        ]);
    }

    private function buildNameGiver(): EventNameGiver
    {
        return new MapNameGiver([
            TestEvent::class => "com.sbooker.test.event",
            OtherEvent::class => "com.sbooker.test.other_event",
        ]);
    }

    private function getEm(): EntityManager
    {
        if (null === $this->em) {
            throw new \RuntimeException("Ebtity manager not initialized");
        }

        return $this->em;
    }
}

class TestEvent extends DomainEvent {

    private string $someValue;

    public function __construct(UuidInterface $entityId, string $someValue, ?Actor $actor = null)
    {
        parent::__construct($entityId, $actor);
        $this->someValue = $someValue;
    }
}

class OtherEvent extends DomainEvent {
    private int $someValue;

    public function __construct(UuidInterface $entityId, int $someValue, ?Actor $actor = null)
    {
        parent::__construct($entityId, $actor);
        $this->someValue = $someValue;
    }
}