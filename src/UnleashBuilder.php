<?php

namespace Unleash\Client;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Exception\CyclicDependencyException;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Helper\Builder\CacheAware;
use Unleash\Client\Helper\Builder\ConfigurationAware;
use Unleash\Client\Helper\Builder\HttpClientAware;
use Unleash\Client\Helper\Builder\MetricsSenderAware;
use Unleash\Client\Helper\Builder\RequestFactoryAware;
use Unleash\Client\Helper\Builder\StaleCacheAware;
use Unleash\Client\Helper\Builder\StickinessCalculatorAware;
use Unleash\Client\Helper\DefaultImplementationLocator;
use Unleash\Client\Helper\UnleashBuilderContainer;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\DefaultUnleashProxyRepository;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\ApplicationHostnameStrategyHandler;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutRandomStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutSessionIdStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutUserIdStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Variant\DefaultVariantHandler;
use Unleash\Client\Variant\VariantHandler;

#[Immutable]
final class UnleashBuilder
{
    private DefaultImplementationLocator $defaultImplementationLocator;

    private ?string $appUrl = null;

    private ?string $instanceId = null;

    private ?string $appName = null;

    private ?ClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?CacheInterface $cache = null;

    private ?CacheInterface $staleCache = null;

    private ?int $cacheTtl = null;

    private ?int $staleTtl = null;

    private ?RegistrationService $registrationService = null;

    private bool $autoregister = true;

    private ?bool $metricsEnabled = null;

    private ?int $metricsInterval = null;

    private ?Context $defaultContext = null;

    private ?UnleashContextProvider $contextProvider = null;

    private ?BootstrapProvider $bootstrapProvider = null;

    private ?BootstrapHandler $bootstrapHandler = null;

    private ?string $proxyKey = null;

    private bool $fetchingEnabled = true;

    /**
     * @var array<string,string>
     */
    private array $headers = [];

    /**
     * @var array<StrategyHandler>
     */
    private array $strategies;

    private ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * @var array<EventSubscriberInterface>
     */
    private array $eventSubscribers = [];

    private ?MetricsHandler $metricsHandler = null;

    private ?VariantHandler $variantHandler = null;

    public function __construct()
    {
        $this->defaultImplementationLocator = new DefaultImplementationLocator();
        if (class_exists(EventDispatcher::class)) {
            $this->eventDispatcher = new EventDispatcher();
        }

        $rolloutStrategyHandler = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
        $this->strategies = [
            new DefaultStrategyHandler(),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            $rolloutStrategyHandler,
            new ApplicationHostnameStrategyHandler(),
            new GradualRolloutUserIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutSessionIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutRandomStrategyHandler($rolloutStrategyHandler),
        ];
    }

    public static function create(): self
    {
        return new self();
    }

    public static function createForGitlab(): self
    {
        return self::create()
            ->withMetricsEnabled(false)
            ->withAutomaticRegistrationEnabled(false);
    }

    #[Pure]
    public function withAppUrl(string $appUrl): self
    {
        return $this->with('appUrl', $appUrl);
    }

    #[Pure]
    public function withInstanceId(string $instanceId): self
    {
        return $this->with('instanceId', $instanceId);
    }

    #[Pure]
    public function withAppName(string $appName): self
    {
        return $this->with('appName', $appName);
    }

    #[Pure]
    public function withGitlabEnvironment(string $environment): self
    {
        return $this->withAppName($environment);
    }

    #[Pure]
    public function withHttpClient(ClientInterface $client): self
    {
        return $this->with('httpClient', $client);
    }

    #[Pure]
    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        return $this->with('requestFactory', $requestFactory);
    }

    #[Pure]
    public function withStrategies(StrategyHandler ...$strategies): self
    {
        return $this->with('strategies', $strategies);
    }

    #[Pure]
    public function withStrategy(StrategyHandler $strategy): self
    {
        return $this->withStrategies(...array_merge($this->strategies, [$strategy]));
    }

    #[Pure]
    public function withCacheHandler(?CacheInterface $cache, ?int $timeToLive = null): self
    {
        $result = $this->with('cache', $cache);
        if ($timeToLive !== null) {
            $result = $result->withCacheTimeToLive($timeToLive);
        }

        return $result;
    }

    #[Pure]
    public function withStaleCacheHandler(?CacheInterface $cache): self
    {
        return $this->with('staleCache', $cache);
    }

    #[Pure]
    public function withCacheTimeToLive(int $timeToLive): self
    {
        return $this->with('cacheTtl', $timeToLive);
    }

    #[Pure]
    public function withHeader(string $header, string $value): self
    {
        return $this->with('headers', array_merge($this->headers, [$header => $value]));
    }

    /**
     * @param array<string, string> $headers
     */
    #[Pure]
    public function withHeaders(array $headers): self
    {
        return $this->with('headers', $headers);
    }

    #[Pure]
    public function withRegistrationService(RegistrationService $registrationService): self
    {
        return $this->with('registrationService', $registrationService);
    }

    #[Pure]
    public function withAutomaticRegistrationEnabled(bool $enabled): self
    {
        return $this->with('autoregister', $enabled);
    }

    #[Pure]
    public function withMetricsEnabled(bool $enabled): self
    {
        return $this->with('metricsEnabled', $enabled);
    }

    #[Pure]
    public function withMetricsInterval(int $milliseconds): self
    {
        return $this->with('metricsInterval', $milliseconds);
    }

    #[Pure]
    public function withContextProvider(?UnleashContextProvider $contextProvider): self
    {
        return $this->with('contextProvider', $contextProvider);
    }

    #[Pure]
    public function withBootstrapHandler(?BootstrapHandler $handler): self
    {
        return $this->with('bootstrapHandler', $handler);
    }

    #[Pure]
    public function withBootstrapProvider(?BootstrapProvider $provider): self
    {
        return $this->with('bootstrapProvider', $provider);
    }

    /**
     * @param array<mixed>|Traversable<mixed>|JsonSerializable|null|string $bootstrap
     */
    #[Pure]
    public function withBootstrap($bootstrap): self
    {
        if ($bootstrap === null) {
            $provider = new EmptyBootstrapProvider();
        } elseif (is_string($bootstrap)) {
            $provider = new JsonBootstrapProvider($bootstrap);
        } else {
            $provider = new JsonSerializableBootstrapProvider($bootstrap);
        }

        return $this->withBootstrapProvider($provider);
    }

    /**
     * @param string|\SplFileInfo|null $file
     */
    #[Pure]
    public function withBootstrapFile($file): self
    {
        if ($file === null) {
            $provider = new EmptyBootstrapProvider();
        } else {
            $provider = new FileBootstrapProvider($file);
        }

        return $this->withBootstrapProvider($provider);
    }

    #[Pure]
    public function withBootstrapUrl(?string $url): self
    {
        return $this->withBootstrapFile($url);
    }

    #[Pure]
    public function withFetchingEnabled(bool $enabled): self
    {
        return $this->with('fetchingEnabled', $enabled);
    }

    #[Pure]
    public function withEventDispatcher(?EventDispatcherInterface $eventDispatcher): self
    {
        return $this->with('eventDispatcher', $eventDispatcher);
    }

    public function withEventSubscriber(EventSubscriberInterface $eventSubscriber): self
    {
        $subscribers = $this->eventSubscribers;
        $subscribers[] = $eventSubscriber;

        return $this->with('eventSubscribers', $subscribers);
    }

    #[Pure]
    public function withStaleTtl(?int $ttl): self
    {
        return $this->with('staleTtl', $ttl);
    }

    #[Pure]
    public function withMetricsHandler(?MetricsHandler $metricsHandler): self
    {
        return $this->with('metricsHandler', $metricsHandler);
    }

    #[Pure]
    public function withVariantHandler(?VariantHandler $variantHandler): self
    {
        return $this->with('variantHandler', $variantHandler);
    }

    #[Pure]
    public function withProxy(?string $proxyKey): self
    {
        return $this->with('proxyKey', $proxyKey);
    }

    public function build(): Unleash
    {
        // basic scalar attributes
        $appUrl = $this->appUrl;
        $instanceId = $this->instanceId;
        $appName = $this->appName;

        if (!$this->fetchingEnabled) {
            $appUrl ??= 'http://127.0.0.1';
            $instanceId ??= 'dev';
            $appName ??= 'dev';
        }

        if ($appUrl === null) {
            throw new InvalidValueException("App url must be set, please use 'withAppUrl()' method");
        }
        if ($instanceId === null) {
            throw new InvalidValueException("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($appName === null) {
            throw new InvalidValueException(
                "App name must be set, please use 'withAppName()' or 'withGitlabEnvironment()' method"
            );
        }

        // PSR services
        $cache = $this->cache;
        if ($cache === null) {
            $cache = $this->defaultImplementationLocator->findCache();
            if ($cache === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No cache implementation provided, please use 'withCacheHandler()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultImplementationLocator->getCachePackages())
                    )
                );
            }
        }
        assert($cache instanceof CacheInterface);
        $staleCache = $this->staleCache ?? $cache;

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            $httpClient = $this->defaultImplementationLocator->findHttpClient();
            if ($httpClient === null) {
                throw new InvalidValueException("No http client provided, please use 'withHttpClient()' method or install a package providing 'psr/http-client-implementation'.");
            }
        }
        assert($httpClient instanceof ClientInterface);

        $requestFactory = $this->requestFactory;
        if ($requestFactory === null) {
            $requestFactory = $this->defaultImplementationLocator->findRequestFactory();
            /**
             * This will only be thrown if a HTTP client was found, but a request factory is not.
             * Due to how php-http/discovery works, this scenario is unlikely to happen.
             * See linked comment for more info.
             *
             * https://github.com/Unleash/unleash-client-php/pull/27#issuecomment-920764416
             */
            // @codeCoverageIgnoreStart
            if ($requestFactory === null) {
                throw new InvalidValueException("No request factory provided, please use 'withRequestFactory()' method or install a package providing 'psr/http-factory-implementation'.");
            }
            // @codeCoverageIgnoreEnd
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        // internal shared services needed for UnleashConfiguration

        $hashCalculator = new MurmurHashCalculator();

        $dependencyContainer = new UnleashBuilderContainer($cache, $staleCache, $httpClient, null, $requestFactory, $hashCalculator, null);

        $contextProvider = $this->contextProvider;
        if ($contextProvider === null) {
            $contextProvider = new DefaultUnleashContextProvider();
        }

        $bootstrapHandler = $this->bootstrapHandler ?? new DefaultBootstrapHandler();
        $bootstrapProvider = $this->bootstrapProvider ?? new EmptyBootstrapProvider();
        $eventDispatcher = $this->eventDispatcher ?? new EventDispatcher();
        foreach ($this->eventSubscribers as $eventSubscriber) {
            $eventDispatcher->addSubscriber($eventSubscriber);
        }

        // initialize services
        $this->initializeServices($contextProvider, $dependencyContainer);
        $this->initializeServices($bootstrapHandler, $dependencyContainer);
        $this->initializeServices($bootstrapProvider, $dependencyContainer);
        if ($eventDispatcher !== null) {
            $this->initializeServices($eventDispatcher, $dependencyContainer);
        }
        foreach ($this->eventSubscribers as $eventSubscriber) {
            $this->initializeServices($eventSubscriber, $dependencyContainer);
        }

        $configuration = new UnleashConfiguration($appUrl, $appName, $instanceId);
        $configuration
            ->setCache($cache)
            ->setStaleCache($staleCache)
            ->setTtl($this->cacheTtl ?? $configuration->getTtl())
            ->setStaleTtl($this->staleTtl ?? $configuration->getStaleTtl())
            ->setMetricsEnabled($this->metricsEnabled ?? $configuration->isMetricsEnabled())
            ->setMetricsInterval($this->metricsInterval ?? $configuration->getMetricsInterval())
            ->setHeaders($this->headers)
            ->setAutoRegistrationEnabled($this->autoregister)
            ->setContextProvider($contextProvider)
            ->setBootstrapHandler($bootstrapHandler)
            ->setBootstrapProvider($bootstrapProvider)
            ->setFetchingEnabled($this->fetchingEnabled)
            ->setEventDispatcher($eventDispatcher)
        ;

        // runtime-unchangeable blocks of Unleash

        $repository = new DefaultUnleashRepository($httpClient, $requestFactory, $configuration);
        $metricsSender = new DefaultMetricsSender($httpClient, $requestFactory, $configuration);

        $dependencyContainer = new UnleashBuilderContainer($cache, $staleCache, $httpClient, $metricsSender, $requestFactory, $hashCalculator, $configuration);

        $registrationService = $this->registrationService;
        if ($registrationService === null) {
            $registrationService = new DefaultRegistrationService($httpClient, $requestFactory, $configuration);
        }

        $metricsHandler = $this->metricsHandler ?? new DefaultMetricsHandler($metricsSender, $configuration);
        $variantHandler = $this->variantHandler ?? new DefaultVariantHandler($hashCalculator);

        // initialization of dependencies

        foreach ($this->strategies as $strategy) {
            $this->initializeServices($strategy, $dependencyContainer);
        }
        $this->initializeServices($repository, $dependencyContainer);
        $this->initializeServices($registrationService, $dependencyContainer);
        $this->initializeServices($metricsHandler, $dependencyContainer);
        $this->initializeServices($variantHandler, $dependencyContainer);

        if ($this->proxyKey !== null) {
            $configuration->setProxyKey($this->proxyKey);
            $proxyRepository = new DefaultUnleashProxyRepository($configuration, $httpClient, $requestFactory);

            return new DefaultProxyUnleash($proxyRepository, $metricsHandler);
        } else {
            return new DefaultUnleash($this->strategies, $repository, $registrationService, $configuration, $metricsHandler, $variantHandler);
        }
    }

    /**
     * @param mixed $value
     */
    private function with(string $property, $value): self
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }

    private function initializeServices(object $target, UnleashBuilderContainer $container): void
    {
        if ($target instanceof CacheAware) {
            $target->setCache($container->getCache());
        }
        if ($target instanceof ConfigurationAware) {
            if ($configuration = $container->getConfiguration()) {
                $target->setConfiguration($configuration);
            } else {
                throw new CyclicDependencyException(sprintf("A dependency '%s' is tagged as ConfigurationAware but that would cause a cyclic dependency as it needs to be part of Configuration", get_class($target)));
            }
        }
        if ($target instanceof HttpClientAware) {
            $target->setHttpClient($container->getHttpClient());
        }
        if ($target instanceof MetricsSenderAware) {
            if ($sender = $container->getMetricsSender()) {
                $target->setMetricsSender($sender);
            } else {
                throw new CyclicDependencyException(sprintf("A dependency '%s' is tagged as MetricsSenderAware but MetricsSender is not available for this type of dependency", get_class($target)));
            }
        }
        if ($target instanceof RequestFactoryAware) {
            $target->setRequestFactory($container->getRequestFactory());
        }
        if ($target instanceof StaleCacheAware) {
            $target->setStaleCache($container->getStaleCache());
        }
        if ($target instanceof StickinessCalculatorAware) {
            $target->setStickinessCalculator($container->getStickinessCalculator());
        }
    }
}
