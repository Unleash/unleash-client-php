<?php

namespace Unleash\Client\Tests;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use ReflectionObject;
use Symfony\Component\Cache\Psr16Cache;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;
use Unleash\Client\UnleashBuilder;

final class UnleashBuilderTest extends TestCase
{
    use RealCacheImplementationTrait;

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
        self::assertNotSame($this->instance, $this->instance->withInstanceId('test'));
    }

    public function testWithHttpClient()
    {
        self::assertNotSame($this->instance, $this->instance->withHttpClient($this->newHttpClient()));
    }

    public function testWithStrategies()
    {
        self::assertNotSame($this->instance, $this->instance->withStrategies(new DefaultStrategyHandler()));
        $strategiesProperty = (new ReflectionObject($this->instance))->getProperty('strategies');
        $strategiesProperty->setAccessible(true);

        self::assertCount(8, $strategiesProperty->getValue($this->instance));
        $instance = $this->instance->withStrategies(new class implements StrategyHandler {
            public function supports(Strategy $strategy): bool
            {
                return false;
            }

            public function getStrategyName(): string
            {
                return '';
            }

            public function isEnabled(Strategy $strategy, Context $context): bool
            {
                return false;
            }
        });
        self::assertCount(1, $strategiesProperty->getValue($instance));
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
            ->withAutomaticRegistrationEnabled(false)
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
        self::assertNotNull($configuration->getCache());
        self::assertIsInt($configuration->getTtl());
        self::assertCount(8, $strategies);

        $requestFactory = $this->newRequestFactory();
        $httpClient = $this->newHttpClient();

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withAutomaticRegistrationEnabled(false)
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
            ->withAutomaticRegistrationEnabled(false)
            ->withStrategies(new DefaultStrategyHandler())
            ->build();
        self::assertCount(1, $strategiesProperty->getValue($instance));

        $cacheHandler = $this->getCache();
        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withAutomaticRegistrationEnabled(false)
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
        self::assertNotSame($this->instance, $this->instance->withAppUrl('https://example.com'));
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

        $unleash = $instance
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test')
            ->withAutomaticRegistrationEnabled(false)
            ->build();
        $reflection = new ReflectionObject($unleash);
        $repositoryProperty = $reflection->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($unleash);

        $reflection = new ReflectionObject($repository);
        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configuration = $configurationProperty->getValue($repository);

        $reflection = new ReflectionObject($configuration);
        $headersPropertyBuilt = $reflection->getProperty('headers');
        $headersPropertyBuilt->setAccessible(true);
        $headersBuilt = $headersPropertyBuilt->getValue($configuration);
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

    public function testWithRegistrationService()
    {
        self::assertNotSame($this->instance, $this->instance->withRegistrationService(new DefaultRegistrationService(
            $this->newHttpClient(),
            $this->newRequestFactory(),
            new UnleashConfiguration('', '', '')
        )));
    }

    public function testWithAutomaticRegistrationEnabled()
    {
        self::assertNotSame($this->instance, $this->instance->withAutomaticRegistrationEnabled(false));
    }

    public function testWithGitlabEnvironment()
    {
        self::assertNotSame($this->instance, $this->instance->withGitlabEnvironment('Test'));
        $instance = $this->instance->withGitlabEnvironment('Test');
        $reflection = new ReflectionObject($instance);
        $appNameProperty = $reflection->getProperty('appName');
        $appNameProperty->setAccessible(true);
        self::assertEquals('Test', $appNameProperty->getValue($instance));
    }

    public function testWithMetricsEnabled()
    {
        self::assertNotSame($this->instance, $this->instance->withMetricsEnabled(false));
    }

    public function testWithMetricsInterval()
    {
        self::assertNotSame($this->instance, $this->instance->withMetricsInterval(5000));
    }

    public function testWithoutDefaultClients()
    {
        $instance = $this->instance
            ->withAppUrl('http://example.com')
            ->withInstanceId('test')
            ->withAppName('test')
        ;
        $unleash = $instance->build();

        $locatorProperty = (new ReflectionObject($instance))->getProperty('defaultImplementationLocator');
        $locatorProperty->setAccessible(true);
        $locator = $locatorProperty->getValue($instance);

        $repositoryProperty = (new ReflectionObject($unleash))->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($unleash);

        $httpClientProperty = (new ReflectionObject($repository))->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClient = $httpClientProperty->getValue($repository);

        self::assertInstanceOf(ClientInterface::class, $httpClient);

        $discoveryStrategies = Psr18ClientDiscovery::getStrategies();
        Psr18ClientDiscovery::setStrategies([]);

        try {
            $instance->build();
            $this->fail('No default http client is available, expected exception');
        } catch (InvalidValueException $e) {
        }

        Psr18ClientDiscovery::setStrategies($discoveryStrategies);

        $configurationProperty = (new ReflectionObject($repository))->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configuration = $configurationProperty->getValue($repository);

        $cacheProperty = (new ReflectionObject($configuration))->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $unleash = $instance->build();
        $repository = $repositoryProperty->getValue($unleash);
        $configuration = $configurationProperty->getValue($repository);
        $cache = $cacheProperty->getValue($configuration);

        self::assertInstanceOf(FilesystemCachePool::class, $cache);

        $defaultImplementationsProperty = (new ReflectionObject($locator))->getProperty('defaultImplementations');
        $defaultImplementationsProperty->setAccessible(true);
        $defaultImplementations = $defaultImplementationsProperty->getValue($locator);

        $defaultImplementations['cache'][FilesystemCachePool::class . 2] = [];
        unset($defaultImplementations['cache'][FilesystemCachePool::class]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        $unleash = $instance->build();
        $repository = $repositoryProperty->getValue($unleash);
        $configuration = $configurationProperty->getValue($repository);
        $cache = $cacheProperty->getValue($configuration);

        self::assertInstanceOf(Psr16Cache::class, $cache);

        $defaultImplementations['cache'][Psr16Cache::class . 2] = [];
        unset($defaultImplementations['cache'][Psr16Cache::class]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        try {
            $instance->build();
            $this->fail('No default cache implementation is available, expected exception');
        } catch (InvalidValueException $e) {
        }
    }

    public function testCreateForGitlab()
    {
        $instance = UnleashBuilder::createForGitlab();

        $autoRegistrationProperty = (new ReflectionObject($instance))->getProperty('autoregister');
        $autoRegistrationProperty->setAccessible(true);
        $metricsProperty = (new ReflectionObject($instance))->getProperty('metricsEnabled');
        $metricsProperty->setAccessible(true);

        self::assertFalse($autoRegistrationProperty->getValue($instance));
        self::assertFalse($metricsProperty->getValue($instance));
    }

    public function testWithStrategy()
    {
        $strategiesProperty = (new ReflectionObject($this->instance))->getProperty('strategies');
        $strategiesProperty->setAccessible(true);

        self::assertCount(8, $strategiesProperty->getValue($this->instance));
        $instance = $this->instance->withStrategy(new class implements StrategyHandler {
            public function supports(Strategy $strategy): bool
            {
                return false;
            }

            public function getStrategyName(): string
            {
                return '';
            }

            public function isEnabled(Strategy $strategy, Context $context): bool
            {
                return false;
            }
        });
        self::assertCount(9, $strategiesProperty->getValue($instance));
    }

    public function testWithDefaultContext()
    {
        self::assertNotSame($this->instance, $this->instance->withDefaultContext(new UnleashContext()));

        $context = new UnleashContext();
        $instance = $this->instance->withDefaultContext($context);
        $defaultContextProperty = (new ReflectionObject($instance))->getProperty('defaultContext');
        $defaultContextProperty->setAccessible(true);
        self::assertSame($context, $defaultContextProperty->getValue($instance));
    }

    public function testWithContextProvider()
    {
        self::assertNotSame($this->instance, $this->instance->withContextProvider(new DefaultUnleashContextProvider()));

        $provider = new DefaultUnleashContextProvider();
        $instance = $this->instance->withContextProvider($provider);
        $providerProperty = (new ReflectionObject($instance))->getProperty('contextProvider');
        $providerProperty->setAccessible(true);
        self::assertSame($provider, $providerProperty->getValue($instance));

        // test that the deprecated withDefaultContext() works
        $defaultContext = new UnleashContext('456');
        $instance = $this->instance
            ->withContextProvider($provider)
            ->withDefaultContext($defaultContext)
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test')
            ->build();

        $configurationProperty = (new ReflectionObject($instance))->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configuration = $configurationProperty->getValue($instance);

        $providerProperty = (new ReflectionObject($configuration))->getProperty('contextProvider');
        $providerProperty->setAccessible(true);
        $provider = $providerProperty->getValue($configuration);
        assert($provider instanceof DefaultUnleashContextProvider);
        self::assertEquals('456', $provider->getContext()->getCurrentUserId());
    }

    private function newHttpClient(): ClientInterface
    {
        return $this->createMock(ClientInterface::class);
    }

    private function newRequestFactory(): RequestFactoryInterface
    {
        return $this->createMock(RequestFactoryInterface::class);
    }
}
