<?php

declare(strict_types=1);

namespace EQS\HealthCheckProvider\HealthChecker;

use EQS\HealthCheckProvider\DTO\CheckDetails;
use EQS\HealthCheckProvider\DTO\Status;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

use function implode;
use function in_array;
use function sprintf;

class HttpHealthChecker implements HealthCheckerInterface
{
    /** @param list<int> $expectedStatusCodes */
    public function __construct(
        private CheckDetails $checkDetails,
        private ClientInterface $httpClient,
        private RequestInterface $request,
        private array $expectedStatusCodes = [200, 204],
        private ?ClockInterface $clock = null,
    ) {
    }

    public function check(): CheckDetails
    {
        $response = null;

        $details = (new CallableHealthChecker($this->checkDetails, function () use (&$response) {
            $response = $this->httpClient->sendRequest($this->request);
        }, 500, $this->clock))->check();

        $status = $response?->getStatusCode();

        if ($details->getStatus() !== Status::healthy || in_array($status, $this->expectedStatusCodes, true)) {
            return $details;
        }

        return $details
            ->withStatus(Status::unhealthy)
            ->withOutput(sprintf(
                'HTTP Status code(s) %s expected, but %s received. Response: %s',
                implode(', ', $this->expectedStatusCodes),
                $status ?: '',
                $response?->getBody()->__tostring() ?: '',
            ));
    }
}
