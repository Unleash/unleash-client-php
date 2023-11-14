<?php

namespace Unleash\Client\Tests\Configuration;

use JetBrains\PhpStorm\Pure;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;

final class UnleashConfigurationTest extends TestCase
{
    use FakeCacheImplementationTrait;

    public function testConstructor()
    {
        $instance = new UnleashConfiguration('https://www.example.com/test', '', '');
        self::assertEquals('https://www.example.com/test/', $instance->getUrl());

        $context = new UnleashContext('147');
        $instance = new UnleashConfiguration(
            'https://www.example.com/test',
            '',
            '',
            null,
            0,
            0,
            false,
            [],
            false,
            $context
        );
        self::assertEquals($context->getCurrentUserId(), $instance->getDefaultContext()->getCurrentUserId());
    }

    public function testSetUrl()
    {
        $instance = new UnleashConfiguration('', '', '');
        $instance->setUrl('https://www.example.com/test');
        self::assertEquals('https://www.example.com/test/', $instance->getUrl());
    }

    public function testGetCache()
    {
        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($this->getCache());
        self::assertInstanceOf(CacheInterface::class, $instance->getCache());

        $instance = new UnleashConfiguration('', '', '');
        $this->expectException(LogicException::class);
        $instance->getCache();
    }

    public function testGetDefaultContext()
    {
        $instance = new UnleashConfiguration('', '', '');
        self::assertInstanceOf(UnleashContext::class, $instance->getDefaultContext());
    }

    public function testSetDefaultContext()
    {
        $context = new UnleashContext('123');
        $instance = (new UnleashConfiguration('', '', ''))
            ->setDefaultContext($context);

        self::assertEquals('123', $instance->getDefaultContext()->getCurrentUserId());
        self::assertSame('123', $instance->getContextProvider()->getContext()->getCurrentUserId());

        $contextProvider = new class implements UnleashContextProvider {
            #[Pure]
            public function getContext(): Context
            {
                return new UnleashContext();
            }
        };

        $instance->setContextProvider($contextProvider);
        $this->expectException(LogicException::class);
        $instance->setDefaultContext($context);
    }

    public function testGetContextProvider()
    {
        $instance = new UnleashConfiguration('', '', '');
        self::assertNotNull($instance->getContextProvider());
    }

    public function testSetContextProvider()
    {
        $instance = new UnleashConfiguration('', '', '');
        $provider = new DefaultUnleashContextProvider();
        $instance->setContextProvider($provider);

        self::assertSame($provider, $instance->getContextProvider());
    }

    public function testGetStaleCache()
    {
        // test that stale cache falls back to normal cache adapter
        $cache = $this->getCache();

        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($cache);

        self::assertSame($cache, $instance->getCache());
        self::assertSame($cache, $instance->getStaleCache());

        $cache1 = $this->getCache();
        $cache2 = $this->getCache();

        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($cache1)
            ->setStaleCache($cache2)
        ;

        self::assertSame($cache1, $instance->getCache());
        self::assertSame($cache2, $instance->getStaleCache());

        $instance = new UnleashConfiguration('', '', '');
        $this->expectException(LogicException::class);
        $instance->getStaleCache();
    }

    public function testGetMetricsUrl()
    {
        $baseInstance = new UnleashConfiguration('http://localhost:3063/api', '', '');

        $resolvedMetricsUrl = $baseInstance->getMetricsUrl();
        self::assertSame($resolvedMetricsUrl, 'http://localhost:3063/api/client/metrics');

        $proxyInstance = new UnleashConfiguration('http://localhost:3063/api', '', '');
        $proxyInstance->setProxyKey('some-key');
        $resolvedMetricsUrl = $proxyInstance->getMetricsUrl();
        self::assertSame($resolvedMetricsUrl, 'http://localhost:3063/api/frontend/client/metrics');
    }
}
