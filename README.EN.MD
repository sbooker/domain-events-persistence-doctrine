[Read in Russian](README.md)

# Doctrine Persistence for Domain Events (`sbooker/doctrine-domain-events-persistence`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

A ready-to-use storage implementation for [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) using Doctrine ORM.

## Library's Purpose

This library provides a single, ready-to-use Doctrine repository that implements **all storage interfaces** defined in `sbooker/domain-events-persistence`:

*   `ConsumeStorage`
*   `SearchStorage`
*   `CleanExpiredStorage`
*   `IdentityStorage`

Using this library saves you from writing your own implementation of these interfaces for Doctrine.

## Installation

```bash
composer require sbooker/domain-events-persistence-doctrine
```
**Dependencies:**
*   `sbooker/domain-events-persistence`
*   `doctrine/orm`

## Usage

### Step 1: Configure the Mapping for `PersistentEvent`

You need to tell Doctrine how to map the `Sbooker\DomainEvents\Persistence\PersistentEvent` entity to the database.

The most common scenario is shown below, where `position` is an auto-incrementing primary key.

**Example Mapping (XML):**
```xml
<!-- config/doctrine/Sbooker.DomainEvents.Persistence.PersistentEvent.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping">
    <entity name="Sbooker\DomainEvents\Persistence\PersistentEvent"
            table="event"
            repository-class="Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository">

        <!-- `position` as an auto-incrementing primary key -->
        <id name="position" type="bigint"> <!-- or integer if you are sure it has enough capacity -->
            <generator strategy="IDENTITY"/>
            <options>
                <option name="unsigned">true</option>
            </options>
        </id>

        <!-- event `id` as a unique identifier (not a primary key) -->
        <field name="id" type="uuid" unique="true" />

        <field name="name" type="string" length="255" />
        <field name="occurredAt" type="datetimetz_immutable" />
        <field name="entityId" type="uuid" />
        <field name="payload" type="json">
            <options>
                <option name="jsonb">true</option>
            </options>
        </field>

        <indexes>
            <!-- Index for searching by event names (for ConsumeStorage) -->
            <index name="ix_event_name" columns="name"/>
            <!-- Index for searching by entity ID (for SearchStorage) -->
            <index name="ix_event_entity_id" columns="entity_id"/>
            <!-- Index for searching by occurrence time (for CleanExpiredStorage and SearchStorage) -->
            <index name="ix_event_occurred_at" columns="occurred_at"/>
        </indexes>

    </entity>
</doctrine-mapping>
```
**Important:** Specify the `repository-class` in the mapping so that Doctrine knows to use the repository from this library for the entity.

### Step 2: Use the Repository in Your DI Container

Now you can get the repository from the `EntityManager` and pass it to the `ConsumerFactory` and other services that need it.

```php
// bootstrap.php or your DI container

use Doctrine\ORM\EntityManagerInterface;
use Sbooker\DomainEvents\Persistence\ConsumerFactory;

/** @var EntityManagerInterface $entityManager */

// 1. Get our ready-to-use repository from the EntityManager
$persistentEventRepository = $entityManager->getRepository(\Sbooker\DomainEvents\Persistence\PersistentEvent::class);

// 2. Pass it to the ConsumerFactory and other services
$consumerFactory = new ConsumerFactory(
    $persistentEventRepository, // Implements ConsumeStorage
    // ...
);

$myAuditService = new MyAuditService(
    $persistentEventRepository // Implements SearchStorage
);
```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence-doctrine.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events-persistence-doctrine.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events-persistence-doctrine.svg?style=flat-square

[release]: https://packagist.org/packages/sbooker/domain-events-persistence-doctrine
[license]: https://github.com/sbooker/domain-events-persistence-doctrine/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events-persistence-doctrine

[composer]: https://getcomposer.org

