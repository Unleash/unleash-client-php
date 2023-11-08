<?php

namespace Unleash\Client\Tests\Client;

use ArrayIterator;
use GuzzleHttp\Psr7\HttpFactory;
use RuntimeException;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Tests\AbstractHttpClientTestCase;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;

final class DefaultRegistrationServiceTest extends AbstractHttpClientTestCase
{
    use RealCacheImplementationTrait {
        RealCacheImplementationTrait::tearDown as cleanupCache;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupCache();
    }

    public function testRegister()
    {
        $configuration = (new UnleashConfiguration('', '', ''))
            ->setHeaders([
                'Some-Header' => 'some-value',
            ])
            ->setCache($this->getCache())
            ->setStaleCache($this->getFreshCacheInstance())
            ->setStaleTtl(0)
            ->setTtl(0);
        $instance = new DefaultRegistrationService(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );

        $this->pushResponse([], 2, 202);
        self::assertTrue($instance->register([
            new DefaultStrategyHandler(),
        ]));
        self::assertTrue($instance->register(new ArrayIterator([
            new DefaultStrategyHandler(),
        ])));

        $this->pushResponse([
            'type' => 'password',
            'path' => '/auth/simple/login',
            'message' => 'You must sign in order to use Unleash',
        ], 1, 401);
        self::assertFalse($instance->register([]));

        $this->pushResponse([], 1, 400);
        self::assertFalse($instance->register([]));

        $configuration->setTtl(30);
        $this->pushResponse([]);
        self::assertTrue($instance->register([]));
        self::assertTrue($instance->register([]));

        $configuration->setFetchingEnabled(false);
        $instance = new DefaultRegistrationService(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );
        self::assertFalse($instance->register([]));
    }

    public function testStaleTtlOnly()
    {
        $configuration = (new UnleashConfiguration('', '', ''))
            ->setHeaders([
                'Some-Header' => 'some-value',
            ])
            ->setCache($this->getCache())
            ->setStaleCache($this->getFreshCacheInstance())
            ->setStaleTtl(30)
            ->setTtl(0);

        $instance = new DefaultRegistrationService(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );

        $this->pushResponse([]);
        self::assertTrue($instance->register([]));
        self::assertTrue($instance->register([]));
    }

    /**
     * @see https://github.com/Unleash/unleash-client-php/issues/132
     */
    public function testRegistrationException()
    {
        $configuration = (new UnleashConfiguration('', '', ''))
            ->setCache($this->getCache())
            ->setTtl(0);
        $instance = new DefaultRegistrationService(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );

        $this->pushResponse(new RuntimeException("This exception shouldn't be propagated"), 1, 404);
        $instance->register([new DefaultStrategyHandler()]);
    }
}
