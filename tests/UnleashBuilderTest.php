<?php

namespace Rikudou\Tests\Unleash;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Rikudou\Tests\Unleash\Traits\RealCacheImplementationTrait;
use Rikudou\Unleash\Client\DefaultRegistrationService;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\Strategy\StrategyHandler;
use Rikudou\Unleash\UnleashBuilder;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\Psr18Client;

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
        self::assertNotSame($this->instance, $this->instance->withHttpClient(new Client()));
    }

    public function testWithStrategies()
    {
        self::assertNotSame($this->instance, $this->instance->withStrategies(new DefaultStrategyHandler()));
        $strategiesProperty = (new ReflectionObject($this->instance))->getProperty('strategies');
        $strategiesProperty->setAccessible(true);

        self::assertCount(7, $strategiesProperty->getValue($this->instance));
        $instance = $this->instance->withStrategies(new class implements StrategyHandler {
            public function supports(Strategy $strategy): bool
            {
                return false;
            }

            public function getStrategyName(): string
            {
                return '';
            }

            public function isEnabled(Strategy $strategy, UnleashContext $context): bool
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
        self::assertNotSame($this->instance, $this->instance->withRequestFactory(new HttpFactory()));
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
        self::assertCount(7, $strategies);

        $requestFactory = new HttpFactory();
        $httpClient = new Client();

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
            new Client(),
            new HttpFactory(),
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

        $defaultImplementationsProperty = (new ReflectionObject($locator))->getProperty('defaultImplementations');
        $defaultImplementationsProperty->setAccessible(true);
        $defaultImplementations = $defaultImplementationsProperty->getValue($locator);

        $repositoryProperty = (new ReflectionObject($unleash))->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($unleash);

        $httpClientProperty = (new ReflectionObject($repository))->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClient = $httpClientProperty->getValue($repository);

        self::assertInstanceOf(Client::class, $httpClient);

        $defaultImplementations['client'][Client::class . 2] = [];
        unset($defaultImplementations['client'][Client::class]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        $unleash = $instance->build();
        $repository = $repositoryProperty->getValue($unleash);
        $httpClient = $httpClientProperty->getValue($repository);

        self::assertInstanceOf(Psr18Client::class, $httpClient);

        $defaultImplementations['client'][Psr18Client::class . 2] = [];
        unset($defaultImplementations['client'][Psr18Client::class]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        try {
            $instance->build();
            $this->fail('No default http client is available, expected exception');
        } catch (InvalidValueException $e) {
        }

        $defaultImplementations['client'][Psr18Client::class] = [];
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        $requestFactoryProperty = (new ReflectionObject($repository))->getProperty('requestFactory');
        $requestFactoryProperty->setAccessible(true);

        $unleash = $instance->build();
        $repository = $repositoryProperty->getValue($unleash);
        $requestFactory = $requestFactoryProperty->getValue($repository);

        self::assertInstanceOf(HttpFactory::class, $requestFactory);

        $defaultImplementations['factory'][HttpFactory::class . 2] = [];
        unset($defaultImplementations['factory'][HttpFactory::class]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        $unleash = $instance->build();
        $repository = $repositoryProperty->getValue($unleash);
        $requestFactory = $requestFactoryProperty->getValue($repository);

        self::assertInstanceOf(Psr18Client::class, $requestFactory);

        $defaultImplementations['factory'][Psr18Client::class . 2] = [];
        unset($defaultImplementations['factory'][Psr18Client::class]);
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

        try {
            $instance->build();
            $this->fail('No default request factory is available, expected exception');
        } catch (InvalidValueException $e) {
        }

        $defaultImplementations['factory'][Psr18Client::class] = [];
        $defaultImplementationsProperty->setValue($locator, $defaultImplementations);

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

        self::assertCount(7, $strategiesProperty->getValue($this->instance));
        $instance = $this->instance->withStrategy(new class implements StrategyHandler {
            public function supports(Strategy $strategy): bool
            {
                return false;
            }

            public function getStrategyName(): string
            {
                return '';
            }

            public function isEnabled(Strategy $strategy, UnleashContext $context): bool
            {
                return false;
            }
        });
        self::assertCount(8, $strategiesProperty->getValue($instance));
    }
}
