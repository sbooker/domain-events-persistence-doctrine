<?php

declare(strict_types=1);

require_once 'bootstrap.php';

return
    Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet(
        \Test\Sbooker\DomainEvents\Persistence\Doctrine\EntityManagerBuilder::me()->get('pgsql11')
    );
