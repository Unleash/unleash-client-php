<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Repository\DefaultUnleashRepository;

abstract class AbstractHttpClientTest extends TestCase
{
    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var DefaultUnleashRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->repository = new DefaultUnleashRepository(
            new Client([
                'handler' => HandlerStack::create($this->mockHandler),
            ]),
            new HttpFactory(),
            new UnleashConfiguration('', '', '')
        );
    }

    protected function tearDown(): void
    {
        self::assertEquals(0, $this->mockHandler->count(), 'Some responses are leftover in the mock queue');
    }

    protected function pushResponse(array $response, int $count = 1): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode($response)));
        }
    }
}
