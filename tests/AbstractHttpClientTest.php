<?php

namespace Rikudou\Tests\Unleash;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Rikudou\Tests\Unleash\Traits\FakeCacheImplementationTrait;
use Rikudou\Unleash\Client\DefaultRegistrationService;
use Rikudou\Unleash\Client\RegistrationService;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Repository\DefaultUnleashRepository;

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
            new UnleashConfiguration('', '', '')
        );

        $this->repository = new DefaultUnleashRepository(
            $this->httpClient,
            new HttpFactory(),
            (new UnleashConfiguration('', '', ''))
                ->setCache($this->getCache())
        );
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
