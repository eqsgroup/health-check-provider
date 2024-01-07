<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider\HealthChecker;

use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;

interface HealthCheckerInterface
{
    public function check(): CheckDetails;
}
