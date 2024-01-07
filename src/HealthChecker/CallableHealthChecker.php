<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider\HealthChecker;

use Closure;
use DateTimeImmutable;
use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;
use Ostrolucky\HealthCheckProvider\DTO\MeasurementName;
use Ostrolucky\HealthCheckProvider\DTO\Status;
use Psr\Clock\ClockInterface;
use Throwable;

class CallableHealthChecker implements HealthCheckerInterface
{
    private Closure $closure;

    /** @param callable(): mixed $callable */
    public function __construct(
        private CheckDetails $checkDetails,
        callable $callable,
        private int $tresholdInMs = 500,
        private ?ClockInterface $clock = null,
    ) {
        $this->closure = Closure::fromCallable($callable);
    }

    public function check(): CheckDetails
    {
        $output = null;
        $status = Status::healthy;
        $startedAt = $this->clock?->now() ?? new DateTimeImmutable();

        try {
            ($this->closure)();
        } catch (Throwable $e) {
            $status = Status::unhealthy;
            $output = $e->getMessage();
        } finally {
            $finishedAt = $this->clock?->now() ?? new DateTimeImmutable();
        }

        $observedValue = $this->getMsSinceUnixEpoch($finishedAt) - $this->getMsSinceUnixEpoch($startedAt);

        if ($status === Status::healthy && $observedValue > $this->tresholdInMs) {
            $status = Status::healthyWithConcerns;
            $output = "Response time threshold {$this->tresholdInMs}ms surpassed";
        }

        return $this->checkDetails
            ->withMeasurementName(MeasurementName::responseTime)
            ->withTime($finishedAt)
            ->withObservedUnit('ms')
            ->withObservedValue($observedValue)
            ->withStatus($status)
            ->withOutput($output);
    }

    private function getMsSinceUnixEpoch(DateTimeImmutable $date): int
    {
        return (int) ($date->getTimestamp() + (int) $date->format('u') / 1000);
    }
}
