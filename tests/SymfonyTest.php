<?php

declare(strict_types=1);

namespace Ostrolucky\Test\HealthCheckProvider;

use Http\Discovery\Psr17Factory;
use Ostrolucky\HealthCheckProvider\DTO\CheckDetails;
use Ostrolucky\HealthCheckProvider\DTO\HealthResponse;
use Ostrolucky\HealthCheckProvider\HealthChecker\CallableHealthChecker;
use Ostrolucky\HealthCheckProvider\RequestHandler;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use function md5;
use function mt_rand;
use function sys_get_temp_dir;

class SymfonyTest extends WebTestCase
{
    use MatchesSnapshots;

    public function testInvoke(): void
    {
        $kernel = new class ('test', false) extends Kernel {
            private ?string $projectDir = null;

            /** @return iterable<Bundle> */
            public function registerBundles(): iterable
            {
                return [new FrameworkBundle()];
            }

            public function registerContainerConfiguration(LoaderInterface $loader)
            {
                $loader->load(static function (ContainerBuilder $container): void {
                    $container->loadFromExtension('framework', [
                        'test' => null,
                        'router' => ['resource' => 'kernel::loadRoutes', 'type' => 'service', 'utf8' => true],
                    ]);

                    $container->register('kernel', self::class)
                        ->addTag('routing.route_loader')
                        ->setAutoconfigured(true)
                        ->setPublic(true);
                });
            }

            public function loadRoutes(LoaderInterface $loader): RouteCollection
            {
                $collection = new RouteCollection();
                $collection->add('healthcheck', new Route('/api/health_check', ['_controller' => 'kernel::index']));

                return $collection;
            }

            public function index(Request $request): Response
            {
                $psr17Factory = new Psr17Factory();
                $psrBridge = new HttpFoundationFactory();

                return $psrBridge->createResponse((
                    (new RequestHandler(
                        new HealthResponse('example-response'),
                        [
                            new CallableHealthChecker(
                                new CheckDetails('example-check'),
                                fn () => true,
                                500,
                                new MockClock('2024-01-01 00:01:00'),
                            ),
                        ],
                        $psr17Factory,
                        $psr17Factory,
                    ))
                        ->handle((new PsrHttpFactory())->createRequest($request))
                ));
            }

            public function getProjectDir(): string
            {
                return $this->projectDir ??= sys_get_temp_dir() . '/sf_kernel_' . md5((string) mt_rand());
            }
        };
        $kernel->boot();

        $client = $kernel->getContainer()->get('test.client');
        self::assertInstanceOf(KernelBrowser::class, $client);
        $client->request('GET', '/api/health_check');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertMatchesJsonSnapshot($client->getResponse()->getContent());
    }
}
