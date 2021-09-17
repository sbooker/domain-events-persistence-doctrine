<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence\Doctrine;

use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\DomainEventCollector;

class Entity implements DomainEntity
{
    use DomainEventCollector;

    private UuidInterface $id;

    public function __construct(UuidInterface $id)
    {
        $this->id = $id;
        $this->publish(new Created($this->id));
    }

    public function update(): void
    {
        $this->publish(new Updated($this->id));
    }
}