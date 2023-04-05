<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence\Doctrine;

use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Persistence\CleanExpiredStorage;
use Sbooker\DomainEvents\Persistence\ConsumeStorage;
use Sbooker\DomainEvents\Persistence\IdentityStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Sbooker\DomainEvents\Persistence\SearchStorage;

final class PersistentEventDoctrineRepository extends EntityRepository implements ConsumeStorage, SearchStorage, CleanExpiredStorage, IdentityStorage
{
    /**
     * @throws Query\QueryException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFirstByPosition(array $eventNames, int $position): ?PersistentEvent
    {
        return
            $this->createQueryBuilder('t')
                ->addCriteria(
                    Criteria::create()
                        ->andWhere(Criteria::expr()->gt('position', $position))
                        ->andWhere(Criteria::expr()->in('name', $eventNames))
                        ->orderBy(['position' => Criteria::ASC])
                        ->setMaxResults(1)

                )
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function findById(UuidInterface $id): ?PersistentEvent
    {
        return
            $this->createQueryBuilder('t')
                ->addCriteria(
                    Criteria::create()->andWhere(Criteria::expr()->eq('id', $id))
                )
                ->getQuery()
                ->getOneOrNullResult();
    }

    /**
     * @throws Query\QueryException
     */
    public function removeExpired(int $retentionPeriod): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->addCriteria(
                Criteria::create()
                    ->andWhere(
                        Criteria::expr()->lte('publishedAt', new \DateTimeImmutable("-{$retentionPeriod}seconds" ))
                    )
            )
            ->getQuery()
            ->execute();
    }

    public function getByEntityIdAndPositions(UuidInterface $entityId, ?int $afterPosition = null, ?int $beforePosition = null, bool $orderByPositionAsc = true): array
    {
        $criteria = $this->getEntityIdCriteria($entityId);

        if (null !== $afterPosition) {
            $criteria->andWhere(Criteria::expr()->gt('position', $afterPosition));
        }
        if (null !== $beforePosition) {
            $criteria->andWhere(Criteria::expr()->lt('position', $beforePosition));
        }

        return $this->getByCriteriaOrderedByPosition($criteria, $orderByPositionAsc);
    }

    public function getByEntityIdAnOccurredAt(UuidInterface $entityId, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null, bool $orderByPositionAsc = true): array
    {
        $criteria = $this->getEntityIdCriteria($entityId);

        if (null !== $after) {
            $criteria->andWhere(Criteria::expr()->gte('occurredAt', $after));
        }
        if (null !== $before) {
            $criteria->andWhere(Criteria::expr()->lte('occurredAt', $before));
        }

        return $this->getByCriteriaOrderedByPosition($criteria, $orderByPositionAsc);
    }

    private function getEntityIdCriteria(UuidInterface $entityId): Criteria
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('entityId', $entityId));

        return $criteria;
    }

    /**
     * @return array<PersistentEvent>
     */
    private function getByCriteriaOrderedByPosition(Criteria $criteria, bool $orderByPositionAsc): array
    {
        $criteria->orderBy(['position' => $orderByPositionAsc ? Criteria::ASC : Criteria::DESC]);

        return $this->createQueryBuilder('t')
            ->addCriteria($criteria)
            ->getQuery()
            ->getResult();
    }
}