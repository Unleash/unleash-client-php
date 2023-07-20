<?php

namespace Unleash\Client\Tests;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Http\Discovery\Psr18ClientDiscovery;
use JsonSerializable;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use ReflectionObject;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Traversable;
use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Bootstrap\DefaultBootstrapHandler;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\Bootstrap\FileBootstrapProvider;
use Unleash\Client\Bootstrap\JsonBootstrapProvider;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\DefaultProxyVariant;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\ProxyVariant;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Exception\CyclicDependencyException;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Tests\TestHelpers\CustomBootstrapProviderImpl74;
use Unleash\Client\Tests\TestHelpers\CustomBootstrapProviderImpl80;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\CacheAwareMetricsHandler;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\ConfigurationAwareContextProvider;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\ConfigurationAwareMetricsHandler;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\ConfigurationAwareRegistrationService;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\ConfigurationAwareVariantHandler;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\HttpClientAwareBootstrapProvider72;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\HttpClientAwareBootstrapProvider80;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\MetricsSenderAwareBootstrapHandler;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\MetricsSenderAwareMetricsHandler;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\RequestFactoryAwareEventDispatcher;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\StaleCacheAwareStrategyHandler;
use Unleash\Client\Tests\TestHelpers\DependencyContainer\StickinessCalculatorAwareEventSubscriber;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Variant\VariantHandler;

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
        self::assertNotSame($this->instance, $this->instance->withRegistrationService(
            new DefaultRegistrationService(
                $this->newHttpClient(),
                $this->newRequestFactory(),
                new UnleashConfiguration('', '', '')
            )
        ));
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
            ->withAutomaticRegistrationEnabled(false)
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

        $defaultImplementationsProperty = (new ReflectionObject($locator))->getProperty('defaultImplementations');
        $defaultImplementationsProperty->setAccessible(true);
        $defaultImplementations = $defaultImplementationsProperty->getValue($locator);

        // cache/filesystem-adapter should be used by default if it's installed
        if (class_exists(FilesystemCachePool::class)) {
            $unleash = $instance->build();
            $repository = $repositoryProperty->getValue($unleash);
            $configuration = $configurationProperty->getValue($repository);
            $cache = $cacheProperty->getValue($configuration);

            self::assertInstanceOf(FilesystemCachePool::class, $cache);

            // Remove cache/filesystem-adapter from the list of available implementations
            // so we can test what happens when it's missing.
            $defaultImplementations['cache'][FilesystemCachePool::class . 2] = [];
            unset($defaultImplementations['cache'][FilesystemCachePool::class]);
            $defaultImplementationsProperty->setValue($locator, $defaultImplementations);
        }

        // symfony/cache should be used by default if it's installed and cache/filesystem-adapter is unavailable
        $unleash = $instance->build();
        $repository = $repositoryProperty->getValue($unleash);
        $configuration = $configurationProperty->getValue($repository);
        $cache = $cacheProperty->getValue($configuration);

        self::assertInstanceOf(Psr16Cache::class, $cache);

        // Remove symfony/cache from the list of available implementations
        // so we can test what happens when it's missing.
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

    public function testWithBootstrapHandler()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withBootstrapHandler(new DefaultBootstrapHandler()));

        $instance = $builder->build();
        self::assertInstanceOf(
            DefaultBootstrapHandler::class,
            $this->getConfiguration($instance)->getBootstrapHandler()
        );

        $instance = $builder->withBootstrapHandler(null)->build();
        self::assertInstanceOf(
            DefaultBootstrapHandler::class,
            $this->getConfiguration($instance)->getBootstrapHandler()
        );

        $handler = new class implements BootstrapHandler {
            public function getBootstrapContents(BootstrapProvider $provider): ?string
            {
                return null;
            }
        };
        $instance = $builder->withBootstrapHandler($handler)->build();
        self::assertInstanceOf(get_class($handler), $this->getConfiguration($instance)->getBootstrapHandler());
    }

    public function testWithBootstrapProvider()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withBootstrapProvider(new EmptyBootstrapProvider()));

        self::assertInstanceOf(
            EmptyBootstrapProvider::class,
            $this->getBootstrapProvider($builder->build())
        );

        self::assertInstanceOf(
            JsonBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrapProvider(new JsonBootstrapProvider('{}'))->build())
        );

        $provider = PHP_VERSION_ID < 80000
            ? new CustomBootstrapProviderImpl74()
            : new CustomBootstrapProviderImpl80();
        self::assertInstanceOf(
            get_class($provider),
            $this->getBootstrapProvider($builder->withBootstrapProvider($provider)->build())
        );
    }

    public function testWithBootstrap()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withBootstrap('{}'));

        self::assertInstanceOf(
            EmptyBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrap(null)->build())
        );

        self::assertInstanceOf(
            JsonBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrap('{}')->build())
        );

        self::assertInstanceOf(
            JsonSerializableBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrap([])->build())
        );
        self::assertInstanceOf(
            JsonSerializableBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrap(
                (function () {
                    yield 1;
                })() // traversable
            )->build())
        );
        self::assertInstanceOf(
            JsonSerializableBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrap(
                new class implements JsonSerializable {
                    public function jsonSerialize(): array
                    {
                        return [];
                    }
                }
            )->build())
        );
    }

    public function testWithBootstrapFile()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withBootstrapFile('/file'));

        self::assertInstanceOf(
            EmptyBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrapFile(null)->build())
        );

        self::assertInstanceOf(
            FileBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrapFile('/file')->build())
        );

        self::assertInstanceOf(
            FileBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrapFile(new \SplFileInfo('/file'))->build())
        );
    }

    public function testWithBootstrapUrl()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withBootstrapUrl('https://getunleash.io'));

        self::assertInstanceOf(
            EmptyBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrapUrl(null)->build())
        );

        self::assertInstanceOf(
            FileBootstrapProvider::class,
            $this->getBootstrapProvider($builder->withBootstrapUrl('https://getunleash.io')->build())
        );
    }

    public function testWithFetchingEnabled()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withFetchingEnabled(true));

        self::assertTrue($this->getConfiguration($builder->build())->isFetchingEnabled());
        self::assertFalse(
            $this->getConfiguration($builder->withFetchingEnabled(false)->build())->isFetchingEnabled()
        );
        self::assertTrue(
            $this->getConfiguration($builder->withFetchingEnabled(true)->build())->isFetchingEnabled()
        );

        // no exception should be thrown
        $this->instance->withFetchingEnabled(false)->build();
    }

    public function testWithEventDispatcher()
    {
        $eventDispatcher = new SymfonyEventDispatcher();
        $instance = $this->instance->withEventDispatcher($eventDispatcher);

        $property = $this->getProperty($instance, 'eventDispatcher');
        self::assertInstanceOf(SymfonyEventDispatcher::class, $property);
        self::assertSame($eventDispatcher, $property);

        $unleash = $instance->withFetchingEnabled(false)->build();
        $configuredEventDispatcher = $this->getConfiguration($unleash)->getEventDispatcherOrNull();
        self::assertInstanceOf(SymfonyEventDispatcher::class, $configuredEventDispatcher);

        $unleash = $this->instance->withFetchingEnabled(false)->build();
        $configuredEventDispatcher = $this->getConfiguration($unleash)->getEventDispatcherOrNull();
        self::assertInstanceOf(SymfonyEventDispatcher::class, $configuredEventDispatcher);

        $unleash = $this->instance->withFetchingEnabled(false)->withEventDispatcher(null)->build();
        $configuredEventDispatcher = $this->getConfiguration($unleash)->getEventDispatcherOrNull();
        self::assertNull($configuredEventDispatcher);
    }

    public function testWithEventSubscriber()
    {
        $eventSubscriber1 = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [UnleashEvents::FEATURE_TOGGLE_DISABLED => 'onDisabled'];
            }

            public function onDisabled(FeatureToggleDisabledEvent $event): void
            {
            }
        };
        $eventSubscriber2 = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [UnleashEvents::FEATURE_TOGGLE_NOT_FOUND => 'onNotFound'];
            }

            public function onNotFound(FeatureToggleNotFoundEvent $event): void
            {
            }
        };

        $instance = $this->instance
            ->withFetchingEnabled(false)
            ->withEventSubscriber($eventSubscriber1)
            ->withEventSubscriber($eventSubscriber2);

        $subscribers = $this->getProperty($instance, 'eventSubscribers');
        self::assertIsArray($subscribers);
        self::assertCount(2, $subscribers);

        self::assertThat(
            $subscribers[0],
            self::logicalOr(
                new IsIdentical($eventSubscriber1),
                new IsIdentical($eventSubscriber2)
            )
        );
        self::assertThat(
            $subscribers[1],
            self::logicalOr(
                new IsIdentical($eventSubscriber1),
                new IsIdentical($eventSubscriber2)
            )
        );

        $unleash = $instance->build();
        $eventDispatcher = $this->getConfiguration($unleash)->getEventDispatcherOrNull();
        self::assertInstanceOf(SymfonyEventDispatcher::class, $eventDispatcher);
        self::assertCount(2, $eventDispatcher->getListeners());
        self::assertCount(1, $eventDispatcher->getListeners(UnleashEvents::FEATURE_TOGGLE_NOT_FOUND));
        self::assertCount(1, $eventDispatcher->getListeners(UnleashEvents::FEATURE_TOGGLE_DISABLED));
    }

    public function testWithStaleTtl()
    {
        $instance = $this->instance->withFetchingEnabled(false);
        self::assertNull($this->getProperty($this->instance, 'staleTtl'));
        self::assertEquals(
            30 * 60,
            $this->getConfiguration($instance->build())->getStaleTtl()
        );

        $instance = $this->instance->withFetchingEnabled(false)->withStaleTtl(60 * 60);
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

        $instance = $this->instance->withFetchingEnabled(false);
        self::assertNull($this->getProperty($instance, 'staleCache'));
        self::assertNotNull($this->getConfiguration($instance->build())->getStaleCache());

        $instance = $this->instance->withFetchingEnabled(false)->withCacheHandler($cache1);
        self::assertNull($this->getProperty($instance, 'staleCache'));
        self::assertSame($cache1, $this->getConfiguration($instance->build())->getStaleCache());

        $instance = $this->instance
            ->withFetchingEnabled(false)
            ->withCacheHandler($cache1)
            ->withStaleCacheHandler($cache2)
        ;
        self::assertSame($cache2, $this->getProperty($instance, 'staleCache'));
        self::assertSame($cache2, $this->getConfiguration($instance->build())->getStaleCache());
        self::assertSame($cache1, $this->getConfiguration($instance->build())->getCache());
    }

    public function testWithMetricsHandler()
    {
        $metricsHandler = new class implements MetricsHandler {
            public function handleMetrics(Feature $feature, bool $successful, ProxyVariant $variant = null): void
            {
            }
        };
        $instance = $this->instance
            ->withMetricsHandler($metricsHandler)
        ;
        self::assertNotSame($this->instance, $instance);
        self::assertSame(
            $metricsHandler,
            $this->getProperty($instance->withFetchingEnabled(false)->build(), 'metricsHandler')
        );
    }

    public function testWithVariantHandler()
    {
        $variantHandler = new class implements VariantHandler {
            public function getDefaultVariant(): ProxyVariant
            {
                return new DefaultProxyVariant('test', false);
            }

            public function selectVariant(Feature $feature, Context $context): ?ProxyVariant
            {
                return null;
            }
        };

        $instance = $this->instance
            ->withVariantHandler($variantHandler)
        ;
        self::assertNotSame($this->instance, $instance);
        self::assertSame(
            $variantHandler,
            $this->getProperty($instance->withFetchingEnabled(false)->build(), 'variantHandler')
        );
    }

    public function testDependencyContainer()
    {
        $base = $this->instance->withFetchingEnabled(false);

        $cacheAwareMetricsHandler = new CacheAwareMetricsHandler();
        self::assertNull($cacheAwareMetricsHandler->cache);
        $instance = $base->withMetricsHandler($cacheAwareMetricsHandler);
        self::assertNull($cacheAwareMetricsHandler->cache);
        $instance->build();
        self::assertNotNull($cacheAwareMetricsHandler->cache);

        $configurationAwareMetricsHandler = new ConfigurationAwareMetricsHandler();
        self::assertNull($configurationAwareMetricsHandler->configuration);
        $instance = $base->withMetricsHandler($configurationAwareMetricsHandler);
        $instance->build();
        self::assertNotNull($configurationAwareMetricsHandler->configuration);

        try {
            $configurationAwareContextProvider = new ConfigurationAwareContextProvider();
            $instance->withContextProvider($configurationAwareContextProvider)->build();
            $this->fail('A cyclic dependency was provided, exception should have been thrown');
        } catch (CyclicDependencyException $e) {
            // ignore
        }

        // these tests also run on php < 8 which doesn't support multiple types
        if (PHP_VERSION_ID >= 80000) {
            $httpClientAwareBootstrapProvider = new HttpClientAwareBootstrapProvider80();
        } else {
            $httpClientAwareBootstrapProvider = new HttpClientAwareBootstrapProvider72();
        }
        self::assertNull($httpClientAwareBootstrapProvider->client);
        $instance->withBootstrapProvider($httpClientAwareBootstrapProvider)->build();
        self::assertNotNull($httpClientAwareBootstrapProvider->client);

        $metricsSenderAwareMetricsHandler = new MetricsSenderAwareMetricsHandler();
        self::assertNull($metricsSenderAwareMetricsHandler->metricsSender);
        $instance->withMetricsHandler($metricsSenderAwareMetricsHandler)->build();
        self::assertNotNull($metricsSenderAwareMetricsHandler);

        try {
            $metricsSenderAwareBootstrapHandler = new MetricsSenderAwareBootstrapHandler();
            $instance->withBootstrapHandler($metricsSenderAwareBootstrapHandler)->build();
            $this->fail('A cyclic dependency was provided, exception should be thrown');
        } catch (CyclicDependencyException $e) {
            // ignore
        }

        $requestFactoryAwareEventDispatcher = new RequestFactoryAwareEventDispatcher();
        self::assertNull($requestFactoryAwareEventDispatcher->requestFactory);
        $instance->withEventDispatcher($requestFactoryAwareEventDispatcher)->build();
        self::assertNotNull($requestFactoryAwareEventDispatcher->requestFactory);

        $stickinessCalculatorAwareEventSubscriber = new StickinessCalculatorAwareEventSubscriber();
        self::assertNull($stickinessCalculatorAwareEventSubscriber->stickinessCalculator);
        $instance->withEventSubscriber($stickinessCalculatorAwareEventSubscriber)->build();
        self::assertNotNull($stickinessCalculatorAwareEventSubscriber->stickinessCalculator);

        $staleCacheAwareStrategyHandler = new StaleCacheAwareStrategyHandler();
        self::assertNull($staleCacheAwareStrategyHandler->cache);
        $instance->withStrategy($staleCacheAwareStrategyHandler)->build();
        self::assertNotNull($staleCacheAwareStrategyHandler->cache);

        $configurationAwareRegistrationService = new ConfigurationAwareRegistrationService();
        self::assertNull($configurationAwareRegistrationService->configuration);
        $instance->withRegistrationService($configurationAwareRegistrationService)->build();
        self::assertNotNull($configurationAwareRegistrationService->configuration);

        $configurationAwareVariantHandler = new ConfigurationAwareVariantHandler();
        self::assertNull($configurationAwareVariantHandler->configuration);
        $instance->withVariantHandler($configurationAwareVariantHandler)->build();
        self::assertNotNull($configurationAwareVariantHandler->configuration);
    }

    private function getConfiguration(DefaultUnleash $unleash): UnleashConfiguration
    {
        $configurationProperty = (new ReflectionObject($unleash))->getProperty('configuration');
        $configurationProperty->setAccessible(true);

        return $configurationProperty->getValue($unleash);
    }

    private function getBootstrapProvider(DefaultUnleash $unleash): BootstrapProvider
    {
        return $this->getConfiguration($unleash)->getBootstrapProvider();
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
