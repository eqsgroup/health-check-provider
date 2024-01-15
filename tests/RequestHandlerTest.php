<?php

declare(strict_types=1);

namespace Ostrolucky\Test\HealthCheckProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;
use Ostrolucky\HealthCheckProvider\DTO\HealthResponse;
use Ostrolucky\HealthCheckProvider\DTO\Status;
use Ostrolucky\HealthCheckProvider\HealthChecker\CallableHealthChecker;
use Ostrolucky\HealthCheckProvider\HealthChecker\DoctrineConnectionHealthChecker;
use Ostrolucky\HealthCheckProvider\HealthChecker\HealthCheckerInterface;
use Ostrolucky\HealthCheckProvider\HealthChecker\HttpHealthChecker;
use Ostrolucky\HealthCheckProvider\RequestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Clock\MockClock;

class RequestHandlerTest extends TestCase
{
    use MatchesSnapshots;

    /** @param list<HealthCheckerInterface> $checks */
    #[DataProvider('handleProvider')]
    public function testHandle(HealthResponse $healthResponse, array $checks, int $expectedStatusCode): void
    {
        $handler = new RequestHandler(
            $healthResponse,
            $checks,
            new ResponseFactory(),
            new StreamFactory(),
        );

        $response = $handler->handle(new ServerRequest());

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertSame(['Content-Type' => ['application/health+json']], $response->getHeaders());
        $this->assertMatchesJsonSnapshot($response->getBody()->__toString());
    }

    /** @return array<string, array{0: HealthResponse, 1: list<HealthCheckerInterface>}> */
    public static function handleProvider(): array
    {
        $clock = new MockClock('2024-01-01 00:01:00');

        return [
            'single check, success' => [
                new HealthResponse(
                    '1.0',
                    'release-id',
                    ['rfc' => 'https://inadarei.github.io/rfc-healthcheck/'],
                    'data-center-api',
                    'Example full healthcheck response',
                ),
                [
                    $successCheck = new CallableHealthChecker(
                        new CheckDetails(
                            'Example',
                            true,
                            affectedEndpoints: ['/api/foo'],
                            componentId: 'baz',
                            componentType: 'component',
                        ),
                        fn () => true,
                        500,
                        $clock,
                    ),
                ],
                200,
            ],
            'single check, fail' => [
                new HealthResponse(),
                [
                    $doctrineFailCheck = new DoctrineConnectionHealthChecker(
                        new CheckDetails('Doctrine', true),
                        new Connection([], new Driver()),
                        $clock,
                    ),
                ],
                503,
            ],
            'all checks fail' => [
                new HealthResponse(),
                [
                    $doctrineFailCheck,
                    new HttpHealthChecker(
                        new CheckDetails('Integrity Line', true),
                        new Client(),
                        new Request('GET', 'not-existing'),
                        clock: $clock,
                    ),
                ],
                503,
            ],
            'multiple checks, one fail' => [
                new HealthResponse(),
                [$successCheck, $doctrineFailCheck],
                503,
            ],
            'multiple checks, non critical fail' => [
                new HealthResponse(),
                [
                    $successCheck,
                    new CallableHealthChecker(
                        new CheckDetails('Minor', false),
                        fn () => throw new Exception('Foo'),
                        clock: $clock,
                    ),
                ],
                200,
            ],
            'multiple checks, one warn' => [
                new HealthResponse(),
                [
                    $successCheck,
                    new HttpHealthChecker(
                        new CheckDetails('Integrity Line', true),
                        new Client(['handler' => new MockHandler([new Response()])]),
                        new Request('GET', 'not-existing'),
                        clock: $clock,
                    ),
                    new class () implements HealthCheckerInterface
                    {
                        public function check(): CheckDetails
                        {
                            return (new CheckDetails('warn', true))->withStatus(Status::healthyWithConcerns);
                        }
                    },
                ],
                200,
            ],
        ];
    }
}
