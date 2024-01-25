<?php

declare(strict_types=1);

namespace EQS\HealthCheckProvider\HealthChecker;

use EQS\HealthCheckProvider\DTO\CheckDetails;

interface HealthCheckerInterface
{
    public function check(): CheckDetails;
}
