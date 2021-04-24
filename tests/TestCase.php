<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Gamez\Symfony\Component\Serializer\Normalizer\UuidNormalizer;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository;
use Sbooker\DomainEvents\Persistence\EventNameGiver;
use Sbooker\DomainEvents\Persistence\EventStorage;
use Sbooker\DomainEvents\Persistence\MapNameGiver;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PersistentPublisher;
use Sbooker\DomainEvents\Persistence\PositionGenerator;
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

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private const DATE_FORMAT = "Y-m-d\TH:i:s.uP";

    private EventNameGiver $nameGiver;

    private Serializer $serializer;

    private TransactionManager $transactionManager;

    private SchemaTool $schemaTool;

    private ?PositionGenerator $positionGenerator = null;

    private PersistentPublisher $publisher;

    public function dbs(): array
    {
        return [
            [ EntityManagerBuilder::PGSQL11 ],
            [ EntityManagerBuilder::PGSQL12 ],
            [ EntityManagerBuilder::MYSQL5 ],
            [ EntityManagerBuilder::MYSQL8 ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->nameGiver = $this->buildNameGiver();
        $this->serializer = $this->buildSerializer();
    }

    final protected function publish(DomainEvent $event): void
    {
        $this->transactionManager->transactional(fn() => $this->publisher->publish($event));
    }

    final protected function setUpDbDeps(string $db): EntityManager
    {
        $em = EntityManagerBuilder::me()->get($db);
        $this->schemaTool = new SchemaTool($em);
        $this->publisher = new PersistentPublisher($this->getEventStorage($em), $this->nameGiver, $this->serializer, $this->positionGenerator);
        $this->transactionManager = new TransactionManager(new DoctrineTransactionHandler($em));
        $this->schemaTool->createSchema($this->getMetadata($em));

        return $em;
    }

    final protected function setPositionGenerator(PositionGenerator $positionGenerator): void
    {
        $this->positionGenerator = $positionGenerator;
    }

    final protected function tearDownDbDeps(EntityManager $em): void
    {
        $this->schemaTool->dropSchema($this->getMetadata($em));
        $this->em = null;
    }

    final protected function getEventStorage(EntityManager $em): PersistentEventDoctrineRepository
    {
        return $em->getRepository(PersistentEvent::class);
    }

    final protected function getTransactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    final protected function getNameGiver(): EventNameGiver
    {
        return $this->nameGiver;
    }

    final protected function getSerializer(): Serializer
    {
        return $this->serializer;
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

    final protected function buildNameGiver(): EventNameGiver
    {
        return new MapNameGiver([
            TestEvent::class => "com.sbooker.test.event",
            OtherEvent::class => "com.sbooker.test.other_event",
        ]);
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