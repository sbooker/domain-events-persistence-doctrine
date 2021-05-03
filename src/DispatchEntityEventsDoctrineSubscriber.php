<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\Publisher;

final class DispatchEntityEventsDoctrineSubscriber implements EventSubscriber
{
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preFlush,
        ];
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        foreach ($args->getEntityManager()->getUnitOfWork()->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                if (!$entity instanceof DomainEntity) {
                    continue;
                }

                $entity->dispatchEvents($this->publisher);
            }
        }
    }
}