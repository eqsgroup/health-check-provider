<?php

declare(strict_types=1);

namespace EQS\HealthCheckProvider\DTO;

/**
 * Indicates whether the service status is acceptable or not.
 *
 * The value of the status field is case-insensitive and is tightly related with the HTTP response code returned by
 * the health endpoint.
 * For "pass" status, HTTP response code in the 2xx-3xx range MUST be used.
 * For "fail" status, HTTP response code in the 4xx-5xx range MUST be used.
 * In case of the "warn" status, endpoints MUST return HTTP status in the 2xx-3xx range, and additional information
 * SHOULD be provided, utilizing optional fields of the response.
 *
 * A health endpoint is only meaningful in the context of the component it indicates the health of.
 * It has no other meaning or purpose. As such, its health is a conduit tothe health of the component.
 * Clients SHOULD assume that the HTTP response code returned by the health endpoint is applicable to the entire
 * component (e.g. a larger API or a microservice). This is compatible with the behavior that current infrastructural
 * tooling expects: load-balancers, service discoveries and others, utilizing health-checks.
 */
enum Status: string
{
    case healthy = 'pass';
    case unhealthy = 'fail';
    case healthyWithConcerns = 'warn';

    public function getResponseCode(): int
    {
        return match ($this) {
            self::healthy => 200,
            self::healthyWithConcerns => 299,
            self::unhealthy => 503,
        };
    }
}
