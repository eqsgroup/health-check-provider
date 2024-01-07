<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider\DTO;

enum MeasurementName: string
{
    case utilization = 'utilization';
    case responseTime = 'responseTime';
    case connections = 'connections';
    case uptime = 'uptime';
}
