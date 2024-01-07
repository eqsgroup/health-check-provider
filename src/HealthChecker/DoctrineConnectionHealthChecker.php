<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider\HealthChecker;

use Doctrine\DBAL\Connection;
use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;
use Psr\Clock\ClockInterface;

class DoctrineConnectionHealthChecker extends CallableHealthChecker
{
    public function __construct(CheckDetails $checkDetails, Connection $connection, ?ClockInterface $clock = null)
    {
        parent::__construct(
            $checkDetails,
            fn () => $connection->executeStatement(
                $connection->getDriver()->getDatabasePlatform()->getDummySelectSQL(),
            ),
            500,
            $clock,
        );
    }
}
