<?php

namespace Unleash\Client\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use ReflectionObject;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\ProxyUnleashBuilder;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;

final class ProxyUnleashBuilderTest extends TestCase
{
    use RealCacheImplementationTrait;

    /**
     * @var ProxyUnleashBuilder
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = ProxyUnleashBuilder::create();
    }

    public function testWithInstanceId()
    {
        self::assertNotSame($this->instance, $this->instance->withInstanceId('test'));
    }

    public function testWithHttpClient()
    {
        self::assertNotSame($this->instance, $this->instance->withHttpClient($this->newHttpClient()));
    }

    public function testWithCacheTimeToLive()
    {
        self::assertNotSame($this->instance, $this->instance->withCacheTimeToLive(123));
    }

    public function testWithAppName()
    {
        self::assertNotSame($this->instance, $this->instance->withAppName('test-app'));
    }

    public function testWithCacheHandler()
    {
        self::assertNotSame($this->instance, $this->instance->withCacheHandler($this->getCache()));
        self::assertNotSame($this->instance, $this->instance->withCacheHandler($this->getCache(), 120));
    }

    public function testWithRequestFactory()
    {
        self::assertNotSame($this->instance, $this->instance->withRequestFactory($this->newRequestFactory()));
    }

    public function testWithHeader()
    {
        self::assertNotSame($this->instance, $this->instance->withHeader('Authorization', 'test'));

        $instance = $this->instance
            ->withHeader('Authorization', 'test')
            ->withHeader('Some-Header', 'test');
        $reflection = new ReflectionObject($instance);
        $headersProperty = $reflection->getProperty('headers');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($instance);
        self::assertCount(2, $headers);

        $instance = $instance
            ->withHeader('Authorization', 'test2');
        $headers = $headersProperty->getValue($instance);
        self::assertCount(2, $headers);
        self::assertArrayHasKey('Authorization', $headers);
        self::assertEquals('test2', $headers['Authorization']);
    }

    private function getConfiguration(DefaultUnleash $unleash): UnleashConfiguration
    {
        $configurationProperty = (new ReflectionObject($unleash))->getProperty('configuration');
        $configurationProperty->setAccessible(true);

        return $configurationProperty->getValue($unleash);
    }

    private function newHttpClient(): ClientInterface
    {
        return $this->createMock(ClientInterface::class);
    }

    private function newRequestFactory(): RequestFactoryInterface
    {
        return $this->createMock(RequestFactoryInterface::class);
    }

    private function getReflection(object $object): ReflectionObject
    {
        return new ReflectionObject($object);
    }

    private function getProperty(object $object, string $property)
    {
        $property = $this->getReflection($object)->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
