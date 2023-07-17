<?php

namespace Unleash\Client\Tests;

use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use ReflectionObject;
use Symfony\Component\Cache\Psr16Cache;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DefaultProxyUnleash;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Metrics\MetricsHandler;
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

        $instance = $instance
            ->withHeaders([
                'Some-Header-2' => 'value',
                'Some-Header-3' => 'value',
            ]);
        $headers = $headersProperty->getValue($instance);
        self::assertCount(2, $headers);
        self::assertArrayHasKey('Some-Header-2', $headers);
        self::assertArrayHasKey('Some-Header-3', $headers);
    }

    public function testWithMetricsInterval()
    {
        self::assertNotSame($this->instance, $this->instance->withMetricsInterval(5000));
    }

    public function testWithMetricsEnabled()
    {
        self::assertNotSame($this->instance, $this->instance->withMetricsEnabled(false));
    }

    public function testWithMetricsHandler()
    {
        $metricsHandler = new class implements MetricsHandler {
            public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
            {
            }
        };
        $instance = $this->instance
            ->withMetricsHandler($metricsHandler)
        ;
        self::assertNotSame($this->instance, $instance);
        self::assertSame(
            $metricsHandler,
            $this->getProperty($instance->withAppUrl('http://test.com')->withInstanceId('test')->withAppName('test-app')->build(), 'metricsHandler')
        );
    }

    public function testWithoutDefaultCache()
    {
        $instance = $this->instance
            ->withAppUrl('http://example.com')
            ->withInstanceId('test')
            ->withAppName('test');

        $unleash = $instance->build();

        $cacheProperty = (new ReflectionObject($unleash))->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $locatorProperty = (new ReflectionObject($instance))->getProperty('defaultImplementationLocator');
        $locatorProperty->setAccessible(true);
        $locator = $locatorProperty->getValue($instance);

        $defaultImplementationsProperty = (new ReflectionObject($locator))->getProperty('defaultImplementations');
        $defaultImplementationsProperty->setAccessible(true);

        $defaultImplementations = $defaultImplementationsProperty->getValue($locator);

        if (class_exists(FilesystemCachePool::class)) {
            $this->assertDefaultCacheImplementation($instance, $cacheProperty, FilesystemCachePool::class, $locator, $defaultImplementationsProperty, $defaultImplementations);
        }

        $this->assertDefaultCacheImplementation($instance, $cacheProperty, Psr16Cache::class, $locator, $defaultImplementationsProperty, $defaultImplementations);

        $this->expectException(InvalidValueException::class);
        $instance->build();
    }

    public function testWithoutDefaultHttpClient()
    {
        $instance = $this->instance
            ->withAppUrl('http://example.com')
            ->withInstanceId('test')
            ->withAppName('test')
        ;

        $locatorProperty = (new ReflectionObject($instance))->getProperty('defaultImplementationLocator');
        $locatorProperty->setAccessible(true);

        $discoveryStrategies = Psr18ClientDiscovery::getStrategies();
        Psr18ClientDiscovery::setStrategies([]);

        try {
            $instance->build();
            $this->fail('No default http client is available, expected exception');
        } catch (InvalidValueException $e) {
            Psr18ClientDiscovery::setStrategies($discoveryStrategies);
        }

        self::assertTrue(true);
    }

    public function testWithStaleTtl()
    {
        $instance = $this->instance->withAppUrl('http://test.com')->withInstanceId('test')->withAppName('test-app');
        self::assertNull($this->getProperty($this->instance, 'staleTtl'));
        self::assertEquals(
            30 * 60,
            $this->getConfiguration($instance->build())->getStaleTtl()
        );

        $instance = $this->instance->withAppUrl('http://test.com')->withInstanceId('test')->withAppName('test-app')->withStaleTtl(60 * 60);
        self::assertNull($this->getProperty($this->instance, 'staleTtl'));
        self::assertEquals(
            60 * 60,
            $this->getConfiguration($instance->build())->getStaleTtl()
        );
    }

    public function testWithStaleCacheHandler()
    {
        $cache1 = $this->getCache();
        $cache2 = $this->getCache();

        $instance = $this->instance->withAppUrl('http://test.com')->withInstanceId('test')->withAppName('test-app');
        self::assertNull($this->getProperty($instance, 'staleCache'));
        self::assertNotNull($this->getConfiguration($instance->build())->getStaleCache());

        $instance = $this->instance->withCacheHandler($cache1)->withAppUrl('http://test.com')->withInstanceId('test')->withAppName('test-app');
        self::assertNull($this->getProperty($instance, 'staleCache'));
        self::assertSame($cache1, $this->getConfiguration($instance->build())->getStaleCache());

        $instance = $this->instance
            ->withCacheHandler($cache1)
            ->withStaleCacheHandler($cache2)
            ->withAppUrl('http://test.com')
            ->withInstanceId('test')
            ->withAppName('test-app')
        ;
        self::assertSame($cache2, $this->getProperty($instance, 'staleCache'));
        self::assertSame($cache2, $this->getConfiguration($instance->build())->getStaleCache());
        self::assertSame($cache1, $this->getConfiguration($instance->build())->getCache());
    }

    public function testBuild()
    {
        try {
            $this->instance->build();
            self::fail('Expected exception: ' . InvalidValueException::class);
        } catch (InvalidValueException $e) {
        }

        try {
            $this->instance
                ->withAppUrl('https://example.com')
                ->build();
            self::fail('Expected exception: ' . InvalidValueException::class);
        } catch (InvalidValueException $e) {
        }

        try {
            $this->instance
                ->withAppUrl('https://example.com')
                ->withInstanceId('test')
                ->build();
            self::fail('Expected exception: ' . InvalidValueException::class);
        } catch (InvalidValueException $e) {
        }

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->build();
        $reflection = new ReflectionObject($instance);

        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configuration = $configurationProperty->getValue($instance);

        self::assertEquals('https://example.com/', $configuration->getUrl());
        self::assertEquals('Test App', $configuration->getAppName());
        self::assertEquals('test', $configuration->getInstanceId());
        self::assertNotNull($configuration->getCache());
        self::assertIsInt($configuration->getTtl());

        $requestFactory = $this->newRequestFactory();
        $httpClient = $this->newHttpClient();

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withRequestFactory($requestFactory)
            ->withHttpClient($httpClient)
            ->build();
    }

    private function getConfiguration(DefaultProxyUnleash $unleash): UnleashConfiguration
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

    private function assertDefaultCacheImplementation($instance, $cacheProperty, $className, $locator, $defaultImplementationsProperty, $defaultImplementations)
    {
        $unleash = $instance->build();
        $cache = $cacheProperty->getValue($unleash);

        self::assertInstanceOf($className, $cache);

        unset($defaultImplementations['cache'][$className]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);
    }
}
