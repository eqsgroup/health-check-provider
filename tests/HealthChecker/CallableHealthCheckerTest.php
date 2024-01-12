<?php

declare(strict_types=1);

namespace Ostrolucky\Test\HealthCheckProvider\HealthChecker;

use DateTimeImmutable;
use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;
use Ostrolucky\HealthCheckProvider\HealthChecker\CallableHealthChecker;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Spatie\Snapshots\MatchesSnapshots;

use function json_encode;

class CallableHealthCheckerTest extends TestCase
{
    use MatchesSnapshots;

    public function testFailOnTimeoutReached(): void
    {
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturnOnConsecutiveCalls(
            new DateTimeImmutable('@1704455198.0166'),
            new DateTimeImmutable('@1704455198.7166'),
        );

        $this->assertMatchesJsonSnapshot(json_encode(
            (new CallableHealthChecker(new CheckDetails('example', true), fn () => true, 500, $clock))->check(),
        ));
    }
}
