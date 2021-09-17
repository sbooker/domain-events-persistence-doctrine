# sbooker/domain-events-persistence-doctrine

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

Doctrine implementation [sbooker/domain-events-persistence-doctrine](https://github.com/sbooker/domain-events-persistence-doctrine)

## Installation
Install via [Composer][composer]:
```bash
composer require sbooker/domain-events-persistence-doctrine
```

### Configuration

In Symfony:
```yaml
config/packages/sbooker_domain_events_persistence_doctrine.yaml

doctrine:
  orm:
    mappings:
      DomainEvents:
        is_bundle: false
        type: xml
        dir: '%kernel.project_dir%/vendor/sbooker/domain-events-persistence-doctrine-doctrine/src/Mapping'
        prefix: Sbooker\DomainEvents\Persistence
``` 

```yaml
# .../services.yaml
services:
  Sbooker\DomainEvents\Persistence\ConsumeStorage:
    alias: Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository

  Sbooker\DomainEvents\Persistence\SearchStorage:
    alias: Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository

  Sbooker\DomainEvents\Persistence\CleanExpiredStorage:
    alias: Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository

  Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository:
    class: Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository
    factory: ['@doctrine', getRepository]
    arguments:
      - Sbooker\DomainEvents\Persistence\PersistentEvent

  Sbooker\PersistentPointer\Repository:
    class: Sbooker\PersistentPointer\Repository
    arguments:
      - '@Sbooker\PersistentPointer\PointerStorage'

  Sbooker\PersistentPointer\PointerStorage:
    alias: Sbooker\PersistentPointer\PersistentPointerDoctrineRepository

  Sbooker\PersistentPointer\PersistentPointerDoctrineRepository:
    class: Sbooker\PersistentPointer\PersistentPointerDoctrineRepository
    factory: [ '@doctrine', getRepository ]
    arguments:
      - Sbooker\PersistentPointer\Pointer

  Sbooker\DomainEvents\Publisher:
    alias: Sbooker\DomainEvents\Persistence\PersistentPublisher

  Sbooker\DomainEvents\Persistence\PersistentPublisher:
    class: Sbooker\DomainEvents\Persistence\PersistentPublisher
    arguments:
      - '@Sbooker\TransactionManager\TransactionManager'
      - '@Sbooker\DomainEvents\Persistence\EventNameGiver'
      - '@serializer'

  Sbooker\DomainEvents\Persistence\EventNameGiver:
    class: Sbooker\DomainEvents\Persistence\MapNameGiver
    arguments:
      - []

  # Each event consumer must inherit this configuration
  Sbooker\DomainEvents\Persistence\Consumer:
    abstract: true
    class: Sbooker\DomainEvents\Persistence\Consumer
    arguments:
      - '@Sbooker\DomainEvents\Persistence\ConsumeStorage'
      - '@Sbooker\TransactionManager\TransactionManager'
      - '@serializer'
      - '@Sbooker\PersistentPointer\Repository'
      - '@Sbooker\DomainEvents\Persistence\EventNameGiver'

  Sbooker\DomainEvents\Persistence\Doctrine\DispatchEntityEventsDoctrineSubscriber:
    class: Sbooker\DomainEvents\Persistence\Doctrine\DispatchEntityEventsDoctrineSubscriber
    arguments:
      - '@Sbooker\DomainEvents\Publisher'
    tags:
      - { name: doctrine.event_subscriber, connection: default }

```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence-doctrine.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events-persistence-doctrine.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events-persistence-doctrine.svg?style=flat-square

[release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence-doctrine
[license]: https://github.com/sbooker/domain-events-persistence-doctrine/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events-persistence-doctrine

[composer]: https://getcomposer.org
