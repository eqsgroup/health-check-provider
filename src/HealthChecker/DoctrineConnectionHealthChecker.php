<?php

declare(strict_types=1);

namespace EQS\HealthCheckProvider\HealthChecker;

use Doctrine\DBAL\Connection;
use EQS\HealthCheckProvider\DTO\CheckDetails;
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
