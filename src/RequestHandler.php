<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider;

use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;
use Ostrolucky\HealthCheckProvider\DTO\HealthResponse;
use Ostrolucky\HealthCheckProvider\DTO\Status;
use Ostrolucky\HealthCheckProvider\HealthChecker\HealthCheckerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_map;
use function count;
use function extension_loaded;
use function implode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class RequestHandler implements RequestHandlerInterface
{
    /** @param iterable<int, HealthCheckerInterface> $checks */
    public function __construct(
        private HealthResponse $healthResponse,
        private iterable $checks,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (extension_loaded('newrelic')) {
            newrelic_ignore_transaction();
        }

        $status = Status::healthy;
        $checks = [];
        $problems = [];
        foreach ($this->checks as $check) {
            $details = $check->check();
            $checks[$details->getName()] = $details;
            $checkStatus = $details->getStatus();

            if ($checkStatus === Status::healthy) {
                continue;
            }

            $problems[] = $details;

            if ($checkStatus === Status::unhealthy || $status === Status::healthy) {
                $status = $checkStatus;
            }
        }

        $problemCount = count($problems);

        return $this->responseFactory->createResponse($status === Status::unhealthy ? 503 : 200)
            ->withHeader('Content-Type', 'application/health+json')
            ->withBody($this->streamFactory->createStream(json_encode(
                $this->healthResponse
                    ->withStatus($status)
                    ->withChecks($checks)
                    ->withOutput(
                        $status === Status::healthy ? null : sprintf(
                            '%d %s:\n\n%s',
                            $problemCount,
                            match ($problemCount) {
                                1 => 'dependency has a problem',
                                default => 'dependencies have problems',
                            },
                            implode(
                                "\n",
                                array_map(
                                    fn (CheckDetails $details) => "{$details->getName()}: {$details->getOutput()}",
                                    $problems,
                                ),
                            ),
                        ),
                    ),
                JSON_THROW_ON_ERROR,
            )));
    }
}
