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
                alias: DomainEvents
``` 


```yaml
# .../services.yaml
services:
  Sbooker\DomainEvents\Persistence\PersistentEventStorage:
    class: Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository
    factory: ['@doctrine', getRepository]
    arguments:
      - Sbooker\DomainEvents\Persistence\PersistentEvent

  Sbooker\DomainEvents\Persistence\ConsumerPositionStorage:
    class: Sbooker\DomainEvents\Persistence\Doctrine\ConsumerPositionDoctrineRepository
    factory: ['@doctrine', getRepository]
    arguments:
      - Sbooker\DomainEvents\Persistence\ConsumerPosition

  Sbooker\DomainEvents\PublisherContainer:
    public: true
    class: Sbooker\DomainEvents\PublisherContainer
    factory: [ Sbooker\DomainEvents\PublisherContainer, 'instance' ]
    calls:
      - method: setPublisher
        arguments:
          - '@Sbooker\DomainEvents\Publisher'

  Sbooker\DomainEvents\Publisher:
    class: Sbooker\DomainEvents\Persistence\PersistentEventPublisher
    arguments:
      - '@Sbooker\DomainEvents\Persistence\PersistentEventStorage'
      - '@serializer'
      - '@Sbooker\DomainEvents\Persistence\EventNameGiver'

  Sbooker\DomainEvents\Persistence\EventNameGiver:
    class: Sbooker\DomainEvents\Persistence\ClassNameNameGiver

  Sbooker\DomainEvents\Persistence\Doctrine\DispatchEntityEventsDoctrineSubscriber:
    class: Sbooker\DomainEvents\Persistence\Doctrine\DispatchEntityEventsDoctrineSubscriber
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
