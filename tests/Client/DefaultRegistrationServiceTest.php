<?php

namespace Rikudou\Tests\Unleash\Client;

use GuzzleHttp\Psr7\HttpFactory;
use Rikudou\Tests\Unleash\AbstractHttpClientTest;
use Rikudou\Tests\Unleash\Traits\RealCacheImplementationTrait;
use Rikudou\Unleash\Client\DefaultRegistrationService;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;

final class DefaultRegistrationServiceTest extends AbstractHttpClientTest
{
    use RealCacheImplementationTrait;

    public function testRegister()
    {
        $configuration = (new UnleashConfiguration('', '', ''))
            ->setHeaders([
                'Some-Header' => 'some-value',
            ])
            ->setCache($this->getCache())
            ->setTtl(0);
        $instance = new DefaultRegistrationService(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );

        $this->pushResponse([], 1, 202);
        self::assertTrue($instance->register([
            new DefaultStrategyHandler(),
        ]));

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
    }
}
