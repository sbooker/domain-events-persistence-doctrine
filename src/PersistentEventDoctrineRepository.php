<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence\Doctrine;

use Sbooker\DomainEvents\Persistence\CleanableStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\EventStorage;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

final class PersistentEventDoctrineRepository extends EntityRepository implements EventStorage, CleanableStorage
{
    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function add(PersistentEvent $event): void
    {
        $this->getEntityManager()->persist($event);
    }

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
}