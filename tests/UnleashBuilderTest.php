<?php

namespace Rikudou\Tests\Unleash;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\UnleashBuilder;

final class UnleashBuilderTest extends TestCase
{
    /**
     * @var UnleashBuilder
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = UnleashBuilder::create();
    }

    public function testWithInstanceId()
    {
        self::assertNotEquals($this->instance, $this->instance->withInstanceId('test'));
    }

    public function testWithHttpClient()
    {
        self::assertNotEquals($this->instance, $this->instance->withHttpClient(new Client()));
    }

    public function testWithStrategies()
    {
        self::assertNotEquals($this->instance, $this->instance->withStrategies(new DefaultStrategyHandler()));
    }

    public function testWithCacheTimeToLive()
    {
        self::assertNotEquals($this->instance, $this->instance->withCacheTimeToLive(123));
    }

    public function testWithAppName()
    {
        self::assertNotEquals($this->instance, $this->instance->withAppName('test-app'));
    }

    public function testWithCacheHandler()
    {
        $cacheHandler = new FilesystemCachePool(
            new Filesystem(new Local(sys_get_temp_dir() . '/unleash-sdk-tests'))
        );
        self::assertNotEquals($this->instance, $this->instance->withCacheHandler($cacheHandler));
        self::assertNotEquals($this->instance, $this->instance->withCacheHandler($cacheHandler, 120));
    }

    public function testWithRequestFactory()
    {
        self::assertNotEquals($this->instance, $this->instance->withRequestFactory(new HttpFactory()));
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
        $repositoryProperty = $reflection->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($instance);
        $strategiesProperty = $reflection->getProperty('strategyHandlers');
        $strategiesProperty->setAccessible(true);
        $strategies = $strategiesProperty->getValue($instance);
        $reflection = new ReflectionObject($repository);
        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configuration = $configurationProperty->getValue($repository);
        assert($configuration instanceof UnleashConfiguration);

        self::assertEquals('https://example.com/', $configuration->getUrl());
        self::assertEquals('Test App', $configuration->getAppName());
        self::assertEquals('test', $configuration->getInstanceId());
        self::assertNull($configuration->getCache());
        self::assertIsInt($configuration->getTtl());
        self::assertCount(7, $strategies);

        $requestFactory = new HttpFactory();
        $httpClient = new Client();

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withRequestFactory($requestFactory)
            ->withHttpClient($httpClient)
            ->build();
        $repository = new ReflectionObject($repositoryProperty->getValue($instance));
        $httpClientProperty = $repository->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $requestFactoryProperty = $repository->getProperty('requestFactory');
        $requestFactoryProperty->setAccessible(true);
        self::assertEquals($httpClient, $httpClientProperty->getValue($repositoryProperty->getValue($instance)));
        self::assertEquals($requestFactory, $requestFactoryProperty->getValue($repositoryProperty->getValue($instance)));

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withStrategies(new DefaultStrategyHandler())
            ->build();
        self::assertCount(1, $strategiesProperty->getValue($instance));

        $cacheHandler = new FilesystemCachePool(
            new Filesystem(new Local(sys_get_temp_dir() . '/unleash-sdk-tests'))
        );
        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withCacheHandler($cacheHandler)
            ->withCacheTimeToLive(359)
            ->build();
        $repository = $repositoryProperty->getValue($instance);
        $configuration = $configurationProperty->getValue($repository);
        assert($configuration instanceof UnleashConfiguration);
        self::assertEquals($cacheHandler, $configuration->getCache());
        self::assertEquals(359, $configuration->getTtl());
    }

    public function testWithAppUrl()
    {
        self::assertNotEquals($this->instance, $this->instance->withAppUrl('https://example.com'));
    }

    public function testWithHeader()
    {
        self::assertNotEquals($this->instance, $this->instance->withHeader('Authorization', 'test'));

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

        $unleash = $instance
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test')
            ->build();
        $reflection = new ReflectionObject($unleash);
        $repositoryProperty = $reflection->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($unleash);

        $reflection = new ReflectionObject($repository);
        $headersPropertyBuilt = $reflection->getProperty('headers');
        $headersPropertyBuilt->setAccessible(true);
        $headersBuilt = $headersPropertyBuilt->getValue($repository);
        self::assertEquals($headers, $headersBuilt);

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
}
