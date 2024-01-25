<h1 align="center">eqs/health-check-provider</h1>

<p align="center">
    <strong>Provides structure for healthcheck endpoints in accordance with IETF's healthcheck draft RFC</strong>
</p>

<!--
TODO: Make sure the following URLs are correct and working for your project.
      Then, remove these comments to display the badges, giving users a quick
      overview of your package.

<p align="center">
    <a href="https://github.com/eqs/health-check-provider"><img src="https://img.shields.io/badge/source-health--check--provider/health--check--provider-blue.svg?style=flat-square" alt="Source Code"></a>
    <a href="https://packagist.org/packages/eqs/health-check-provider"><img src="https://img.shields.io/packagist/v/eqs/health-check-provider.svg?style=flat-square&label=release" alt="Download Package"></a>
    <a href="https://php.net"><img src="https://img.shields.io/packagist/php-v/eqs/health-check-provider.svg?style=flat-square&colorB=%238892BF" alt="PHP Programming Language"></a>
    <a href="https://github.com/eqs/health-check-provider/blob/main/LICENSE"><img src="https://img.shields.io/packagist/l/eqs/health-check-provider.svg?style=flat-square&colorB=darkcyan" alt="Read License"></a>
    <a href="https://github.com/eqs/health-check-provider/actions/workflows/continuous-integration.yml"><img src="https://img.shields.io/github/actions/workflow/status/eqs/health-check-provider/continuous-integration.yml?branch=main&style=flat-square&logo=github" alt="Build Status"></a>
    <a href="https://codecov.io/gh/eqs/health-check-provider"><img src="https://img.shields.io/codecov/c/gh/eqs/health-check-provider?label=codecov&logo=codecov&style=flat-square" alt="Codecov Code Coverage"></a>
    <a href="https://shepherd.dev/github/eqs/health-check-provider"><img src="https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fshepherd.dev%2Fgithub%2Fostrolucky%2Fhealth-check-provider2%2Fcoverage" alt="Psalm Type Coverage"></a>
</p>
-->


## About
Package provides endpoints which conform to <a href="https://datatracker.ietf.org/doc/html/draft-inadarei-api-health-check-06">draft 06 version of IETF's healthcheck RFC</a>.

### Integrations
We are shipping following integrations, but it's very easy to implement your own by reusing [CallableHealthChecker](src/HealthChecker/CallableHealthChecker.php):
- HTTP request
- Doctrine Connection

## Installation

Install this package as a dependency using [Composer](https://getcomposer.org).

``` bash
composer require eqs/health-check-provider
```

## Usage

This library provides [PSR-15 HTTP Server Request Handler](https://www.php-fig.org/psr/psr-15/), which guarantees
compatibility with wide range of PHP frameworks. In case your framework does not natively support it, you can find
a [PSR bridge](https://symfony.com/doc/current/components/psr7.html) which supports it.

<details>
<summary>Example controller for Symfony framework</summary>

For this example, on top of standard symfony packages, you also need `php-http/discovery` and `symfony/psr-http-message-bridge` packages.

```php
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\HttpFactory;
use EQS\HealthCheckProvider\DTO\CheckDetails;
use EQS\HealthCheckProvider\DTO\HealthResponse;
use EQS\HealthCheckProvider\HealthChecker\CallableHealthChecker;
use EQS\HealthCheckProvider\HealthChecker\DoctrineConnectionHealthChecker;
use EQS\HealthCheckProvider\HealthChecker\HttpHealthChecker;
use EQS\HealthCheckProvider\RequestHandler;
use Psr\Http\Client\ClientInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Annotation\Route;

class GetHealthCheckController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'messenger.transport.amqp_dc_user_update')]
        private MessageCountAwareInterface&TransportInterface $transport,
        private Connection $connection,
        private ClientInterface $httpClient,
    ) {}

    #[Route(path: '/api/health_check')]
    public function __invoke(Request $request): Response
    {
        $psr17Factory = new HttpFactory();
        $psrBridge = new HttpFoundationFactory();

        return $psrBridge->createResponse(
            (new RequestHandler(
                new HealthResponse(),
                [
                    new CallableHealthChecker(new CheckDetails('AMQP', true), fn () => $this->transport->getMessageCount()),
                    new DoctrineConnectionHealthChecker(new CheckDetails('Database', true), $this->connection),
                    new HttpHealthChecker(
                        new CheckDetails('External API', false),
                        $this->httpClient,
                        new \GuzzleHttp\Psr7\Request('GET', 'https://www.google.com'),
                    ),
                ],
                $psr17Factory,
                $psr17Factory,
            ))
                ->handle((new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
                    ->createRequest($request)),
        );
    }
}
```
</details>

## Contributing

Contributions are welcome! To contribute, please familiarize yourself with
[CONTRIBUTING.md](CONTRIBUTING.md).







## Copyright and License

eqs/health-check-provider is copyright © [EQS Group](https://www.eqs.com/)
and licensed for use under the terms of the
MIT License (MIT). Please see [LICENSE](LICENSE) for more information.


