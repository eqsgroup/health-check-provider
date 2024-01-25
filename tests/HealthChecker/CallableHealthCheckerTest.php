<?php

declare(strict_types=1);

namespace EQS\Test\HealthCheckProvider\HealthChecker;

use DateTimeImmutable;
use EQS\HealthCheckProvider\DTO\CheckDetails;
use EQS\HealthCheckProvider\HealthChecker\CallableHealthChecker;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Spatie\Snapshots\MatchesSnapshots;

use function json_encode;

class CallableHealthCheckerTest extends TestCase
{
    use MatchesSnapshots;

    #[TestWith(['@1704455198.0166', '@1704455198.7166'])]
    #[TestWith(['2024-01-15 12:43:41.328996', '2024-01-15 12:43:48.551069'])]
    public function testFailOnTimeoutReached(string $start, string $finish): void
    {
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')
            ->willReturnOnConsecutiveCalls(new DateTimeImmutable($start), new DateTimeImmutable($finish));

        $this->assertMatchesJsonSnapshot(json_encode(
            (new CallableHealthChecker(new CheckDetails('example', true), fn () => true, 500, $clock))->check(),
        ));
    }
}
