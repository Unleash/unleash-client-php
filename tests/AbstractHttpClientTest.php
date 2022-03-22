<?php

namespace Unleash\Client\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\VariantHandler;

abstract class AbstractHttpClientTest extends TestCase
{
    use FakeCacheImplementationTrait;

    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var DefaultUnleashRepository
     */
    protected $repository;

    /**
     * @var array[]
     */
    protected $requestHistory = [];

    /**
     * @var HandlerStack
     */
    protected $handlerStack;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var RegistrationService
     */
    protected $registrationService;

    /**
     * @var MetricsHandler
     */
    protected $metricsHandler;

    /**
     * @var VariantHandler
     */
    protected $variantHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->requestHistory));

        $this->httpClient = new Client([
            'handler' => $this->handlerStack,
        ]);

        $this->registrationService = new DefaultRegistrationService(
            $this->httpClient,
            new HttpFactory(),
            (new UnleashConfiguration('', '', ''))
                ->setCache($this->getCache())
        );

        $this->repository = new DefaultUnleashRepository(
            $this->httpClient,
            new HttpFactory(),
            (new UnleashConfiguration('', '', ''))
                ->setCache($this->getCache())
        );

        $this->metricsHandler = new class implements MetricsHandler {
            public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
            {
            }
        };

        $this->variantHandler = new class implements VariantHandler {
            public function getDefaultVariant(): Variant
            {
                return new DefaultVariant('test', false);
            }

            public function selectVariant(Feature $feature, Context $context): ?Variant
            {
                return null;
            }
        };
    }

    protected function tearDown(): void
    {
        self::assertEquals(0, $this->mockHandler->count(), 'Some responses are leftover in the mock queue');
    }

    protected function pushResponse(array $response, int $count = 1, int $statusCode = 200): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->mockHandler->append(new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($response)));
        }
    }
}
