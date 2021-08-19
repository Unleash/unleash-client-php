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
}
