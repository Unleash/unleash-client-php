<?php

namespace Rikudou\Unleash;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Repository\DefaultUnleashRepository;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;
use Rikudou\Unleash\Strategy\StrategyHandler;
use Rikudou\Unleash\Strategy\UserIdStrategyHandler;

#[Immutable]
final class UnleashBuilder
{
    private ?string $appUrl = null;

    private ?string $instanceId = null;

    private ?string $appName = null;

    private ?ClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?CacheInterface $cache = null;

    private ?int $cacheTtl = null;

    /**
     * @var array<string,string>
     */
    private array $headers = [];

    /**
     * @var array<StrategyHandler>|null
     */
    private ?array $strategies = null;

    #[Pure]
    public static function create(): self
    {
        return new self();
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
    public function withCacheHandler(?CacheInterface $cache, ?int $timeToLive = null): self
    {
        $result = $this->with('cache', $cache);
        if ($timeToLive !== null) {
            $result = $result->withCacheTimeToLive($timeToLive);
        }

        return $result;
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

    public function build(): Unleash
    {
        if ($this->appUrl === null) {
            throw new InvalidValueException("App url must be set, please use 'withAppUrl()' method");
        }
        if ($this->instanceId === null) {
            throw new InvalidValueException("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($this->appName === null) {
            throw new InvalidValueException("App name must be set, please use 'withAppName()' method");
        }

        $configuration = new UnleashConfiguration($this->appUrl, $this->appName, $this->instanceId);
        $configuration
            ->setCache($this->cache)
            ->setTtl($this->cacheTtl ?? $configuration->getTtl());

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            if (class_exists(Client::class)) {
                $httpClient = new Client();
            } else {
                throw new InvalidValueException(
                    "No http client provided and Guzzle is not installed, please use 'withHttpClient()' method"
                );
            }
        }
        assert($httpClient instanceof ClientInterface);

        $requestFactory = $this->requestFactory;
        if ($requestFactory === null) {
            if (class_exists(HttpFactory::class)) {
                $requestFactory = new HttpFactory();
            } else {
                throw new InvalidValueException(
                    "No request factory provided and Guzzle is not installed, please use 'withRequestFactory()' method"
                );
            }
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        $repository = new DefaultUnleashRepository($httpClient, $requestFactory, $configuration, $this->headers);

        $strategies = $this->strategies;
        if ($strategies === null || !count($strategies)) {
            $strategies = [
                new DefaultStrategyHandler(),
                new IpAddressStrategyHandler(),
                new UserIdStrategyHandler(),
                new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
            ];
        }

        return new DefaultUnleash($strategies, $repository);
    }

    private function with(string $property, mixed $value): self
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }
}
