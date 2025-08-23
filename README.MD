[Read in English](README.EN.MD)

# Doctrine Persistence for Domain Events (`sbooker/doctrine-domain-events-persistence`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

Готовая реализация хранилищ для [sbooker/domain-events-persistence](https://github.com/sbooker/domain-events-persistence) с использованием Doctrine ORM.

## Назначение библиотеки

Эта библиотека предоставляет единый, готовый к использованию Doctrine-репозиторий, который реализует **все интерфейсы хранения**, определенные в `sbooker/domain-events-persistence`:

*   `ConsumeStorage`
*   `SearchStorage`
*   `CleanExpiredStorage`
*   `IdentityStorage`

Использование этой библиотеки избавляет вас от необходимости писать собственную реализацию этих интерфейсов для Doctrine.

## Установка

```bash
composer require sbooker/domain-events-persistence-doctrine
```
**Зависимости:**
*   `sbooker/domain-events-persistence`
*   `doctrine/orm`

## Использование

### Шаг 1: Настройте маппинг для `PersistentEvent`

Вам необходимо сообщить Doctrine, как сохранять сущность `Sbooker\DomainEvents\Persistence\PersistentEvent` в базу данных.

Ниже приведен наиболее распространенный сценарий, где position является автоинкрементным первичным ключом.

**Пример маппинга (XML):**
```xml
<!-- config/doctrine/Sbooker.DomainEvents.Persistence.PersistentEvent.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping">
    <entity name="Sbooker\DomainEvents\Persistence\PersistentEvent"
            table="event"
            repository-class="Sbooker\DomainEvents\Persistence\Doctrine\PersistentEventDoctrineRepository">

        <!-- `position` как автоинкрементный первичный ключ -->
        <id name="position" type="bigint"> <!-- или integer, если уверены что емкости хватит -->
            <generator strategy="IDENTITY"/>
            <options>
                <option name="unsigned">true</option>
            </options>
        </id>

        <!-- `id` события как уникальный идентификатор (не первичный ключ) -->
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
            <!-- Индекс для поиска по именам событий (для ConsumeStorage) -->
            <index name="ix_event_name" columns="name"/>
            <!-- Индекс для поиска по ID сущности (для SearchStorage) -->
            <index name="ix_event_entity_id" columns="entity_id"/>
            <!-- Индекс для поиска по времени возникновения события (для CleanExpiredStorage и SearchStorage) -->
            <index name="ix_event_occurred_at" columns="occurred_at"/>
        </indexes>

    </entity>
</doctrine-mapping>
```
**Важно:** Укажите `repository-class` в маппинге, чтобы Doctrine знала, что для этой сущности нужно использовать репозиторий из этой библиотеки.

### Шаг 2: Используйте репозиторий в DI-контейнере

Теперь вы можете получить репозиторий из `EntityManager` и передать его в `ConsumerFactory` и другие сервисы, которым он нужен.

```php
// bootstrap.php или ваш DI-контейнер

use Doctrine\ORM\EntityManagerInterface;
use Sbooker\DomainEvents\Persistence\ConsumerFactory;

/** @var EntityManagerInterface $entityManager */

// 1. Получаем наш готовый репозиторий из EntityManager
$persistentEventRepository = $entityManager->getRepository(\Sbooker\DomainEvents\Persistence\PersistentEvent::class);

// 2. Передаем его в ConsumerFactory и другие сервисы
$consumerFactory = new ConsumerFactory(
    $persistentEventRepository, // Реализует ConsumeStorage
    // ...
);

$myAuditService = new MyAuditService(
    $persistentEventRepository // Реализует SearchStorage
);
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
