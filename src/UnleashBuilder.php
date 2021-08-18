<?php

namespace Unleash\Client;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Helper\DefaultImplementationLocator;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutRandomStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutSessionIdStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutUserIdStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Variant\DefaultVariantHandler;

#[Immutable]
final class UnleashBuilder
{
    /**
     * @var \Unleash\Client\Helper\DefaultImplementationLocator
     */
    private $defaultImplementationLocator;

    /**
     * @var string|null
     */
    private $appUrl;

    /**
     * @var string|null
     */
    private $instanceId;

    /**
     * @var string|null
     */
    private $appName;

    /**
     * @var \Psr\Http\Client\ClientInterface|null
     */
    private $httpClient;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface|null
     */
    private $requestFactory;

    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $cache;

    /**
     * @var int|null
     */
    private $cacheTtl;

    /**
     * @var \Unleash\Client\Client\RegistrationService|null
     */
    private $registrationService;

    /**
     * @var bool
     */
    private $autoregister = true;

    /**
     * @var bool|null
     */
    private $metricsEnabled;

    /**
     * @var int|null
     */
    private $metricsInterval;

    /**
     * @var \Unleash\Client\Configuration\Context|null
     */
    private $defaultContext;

    /**
     * @var array<string,string>
     */
    private $headers = [];

    /**
     * @var array<StrategyHandler>
     */
    private $strategies;

    #[Pure]
    public function __construct()
    {
        $this->defaultImplementationLocator = new DefaultImplementationLocator();

        $rolloutStrategyHandler = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
        $this->strategies = [
            new DefaultStrategyHandler(),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            $rolloutStrategyHandler,
            new GradualRolloutUserIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutSessionIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutRandomStrategyHandler($rolloutStrategyHandler),
        ];
    }

    /**
     * @return $this
     */
    #[Pure]
    public static function create()
    {
        return new self();
    }

    /**
     * @return $this
     */
    #[Pure]
    public static function createForGitlab()
    {
        return self::create()
            ->withMetricsEnabled(false)
            ->withAutomaticRegistrationEnabled(false);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withAppUrl(string $appUrl)
    {
        return $this->with('appUrl', $appUrl);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withInstanceId(string $instanceId)
    {
        return $this->with('instanceId', $instanceId);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withAppName(string $appName)
    {
        return $this->with('appName', $appName);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withGitlabEnvironment(string $environment)
    {
        return $this->withAppName($environment);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withHttpClient(ClientInterface $client)
    {
        return $this->with('httpClient', $client);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withRequestFactory(RequestFactoryInterface $requestFactory)
    {
        return $this->with('requestFactory', $requestFactory);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withStrategies(StrategyHandler ...$strategies)
    {
        return $this->with('strategies', $strategies);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withStrategy(StrategyHandler $strategy)
    {
        return $this->withStrategies(...array_merge($this->strategies, [$strategy]));
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withCacheHandler(?CacheInterface $cache, ?int $timeToLive = null)
    {
        $result = $this->with('cache', $cache);
        if ($timeToLive !== null) {
            $result = $result->withCacheTimeToLive($timeToLive);
        }

        return $result;
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withCacheTimeToLive(int $timeToLive)
    {
        return $this->with('cacheTtl', $timeToLive);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withHeader(string $header, string $value)
    {
        return $this->with('headers', array_merge($this->headers, [$header => $value]));
    }

    /**
     * @param array<string, string> $headers
     * @return $this
     */
    #[Pure]
    public function withHeaders(array $headers)
    {
        return $this->with('headers', $headers);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withRegistrationService(RegistrationService $registrationService)
    {
        return $this->with('registrationService', $registrationService);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withAutomaticRegistrationEnabled(bool $enabled)
    {
        return $this->with('autoregister', $enabled);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withMetricsEnabled(bool $enabled)
    {
        return $this->with('metricsEnabled', $enabled);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withMetricsInterval(int $milliseconds)
    {
        return $this->with('metricsInterval', $milliseconds);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withDefaultContext(?Context $context)
    {
        return $this->with('defaultContext', $context);
    }

    public function build(): Unleash
    {
        if ($this->appUrl === null) {
            throw new InvalidValueException("App url must be set, please use 'withAppUrl()' method");
        }
        if ($this->instanceId === null) {
            throw new InvalidValueException("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($this->appName === null) {
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

        $configuration = new UnleashConfiguration($this->appUrl, $this->appName, $this->instanceId);
        $configuration
            ->setCache($cache)
            ->setTtl($this->cacheTtl ?? $configuration->getTtl())
            ->setMetricsEnabled($this->metricsEnabled ?? $configuration->isMetricsEnabled())
            ->setMetricsInterval($this->metricsInterval ?? $configuration->getMetricsInterval())
            ->setHeaders($this->headers)
            ->setAutoRegistrationEnabled($this->autoregister)
            ->setDefaultContext($this->defaultContext)
        ;

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            $httpClient = $this->defaultImplementationLocator->findHttpClient();
            if ($httpClient === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No http client provided, please use 'withHttpClient()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultImplementationLocator->getHttpClientPackages())
                    )
                );
            }
        }
        assert($httpClient instanceof ClientInterface);

        $requestFactory = $this->requestFactory;
        if ($requestFactory === null) {
            $requestFactory = $this->defaultImplementationLocator->findRequestFactory();
            if ($requestFactory === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No request factory provided, please use 'withHttpClient()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultImplementationLocator->getRequestFactoryPackages())
                    )
                );
            }
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        $repository = new DefaultUnleashRepository($httpClient, $requestFactory, $configuration);

        $hashCalculator = new MurmurHashCalculator();

        $registrationService = $this->registrationService;
        if ($registrationService === null) {
            $registrationService = new DefaultRegistrationService($httpClient, $requestFactory, $configuration);
        }

        return new DefaultUnleash($this->strategies, $repository, $registrationService, $configuration, new DefaultMetricsHandler(
            new DefaultMetricsSender($httpClient, $requestFactory, $configuration),
            $configuration
        ), new DefaultVariantHandler($hashCalculator));
    }

    /**
     * @param mixed $value
     * @return $this
     */
    private function with(string $property, $value)
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }
}
