<?php

namespace Unleash\Client;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\SettableUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Helper\DefaultImplementationLocator;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Metrics\MetricsHandler;

#[Immutable]
final class ProxyUnleashBuilder
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

    private ?bool $metricsEnabled = null;

    private ?int $metricsInterval = null;

    private ?Context $defaultContext = null;

    private ?MetricsHandler $metricsHandler = null;

    private ?UnleashContextProvider $contextProvider = null;

    /**
     * @var array<string,string>
     */
    private array $headers = [];

    public function __construct()
    {
        $this->defaultImplementationLocator = new DefaultImplementationLocator();
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
    public function withStaleTtl(?int $ttl): self
    {
        return $this->with('staleTtl', $ttl);
    }

    #[Pure]
    public function withMetricsHandler(MetricsHandler $metricsHandler): self
    {
        return $this->with('metricsHandler', $metricsHandler);
    }

    public function build(): DefaultProxyUnleash
    {
        $appUrl = $this->appUrl;
        $instanceId = $this->instanceId;
        $appName = $this->appName;

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

        $contextProvider = $this->contextProvider;
        if ($contextProvider === null) {
            $contextProvider = new DefaultUnleashContextProvider();
        }
        if ($this->defaultContext !== null && $contextProvider instanceof SettableUnleashContextProvider) {
            $contextProvider->setDefaultContext($this->defaultContext);
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
            ->setContextProvider($contextProvider)
        ;

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            $httpClient = $this->defaultImplementationLocator->findHttpClient();
            if ($httpClient === null) {
                throw new InvalidValueException(
                    "No http client provided, please use 'withHttpClient()' method or install a package providing 'psr/http-client-implementation'.",
                );
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
                throw new InvalidValueException(
                    "No request factory provided, please use 'withRequestFactory()' method or install a package providing 'psr/http-factory-implementation'.",
                );
            }
            // @codeCoverageIgnoreEnd
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        $metricsSender = new DefaultMetricsSender(
            $httpClient,
            $requestFactory,
            $configuration,
        );

        $metricsHandler = $this->metricsHandler ?? new DefaultMetricsHandler($metricsSender, $configuration);

        return new DefaultProxyUnleash(
            $appUrl,
            $configuration,
            $httpClient,
            $requestFactory,
            $metricsHandler,
            $cache,
        );
    }

    private function with(string $property, mixed $value): self
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }
}
