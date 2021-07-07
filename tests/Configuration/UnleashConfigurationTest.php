<?php

namespace Rikudou\Tests\Unleash\Configuration;

use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Rikudou\Tests\Unleash\Traits\FakeCacheImplementationTrait;
use Rikudou\Unleash\Configuration\UnleashConfiguration;

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
}
