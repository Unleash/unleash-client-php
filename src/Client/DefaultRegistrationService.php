<?php

namespace Unleash\Client\Client;

use DateTimeImmutable;
use Exception;
use JsonException;
use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Enum\CacheKey;
use Unleash\Client\Helper\StringStream;
use Unleash\Client\Helper\Url;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Unleash;

final class DefaultRegistrationService implements RegistrationService
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly UnleashConfiguration $configuration,
        private ?string $sdkName = Unleash::SDK_NAME,
        private ?string $sdkVersion = Unleash::SDK_VERSION,
    ) {
    }

    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     *
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    #[Override]
    public function register(iterable $strategyHandlers): bool
    {
        if (!$this->configuration->isFetchingEnabled()) {
            return false;
        }
        if ($this->hasValidCacheRegistration()) {
            return true;
        }
        if (!is_array($strategyHandlers)) {
            $strategyHandlers = iterator_to_array($strategyHandlers);
        }
        $request = $this->requestFactory
            ->createRequest('POST', (string) Url::appendPath($this->configuration->getUrl(), 'client/register'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'sdkVersion' => $this->sdkName . ':' . $this->sdkVersion,
                'strategies' => array_map(fn (StrategyHandler $strategyHandler): string => $strategyHandler->getStrategyName(), $strategyHandlers),
                'started' => (new DateTimeImmutable())->format('c'),
                'interval' => $this->configuration->getMetricsInterval(),
                'platformName' => PHP_SAPI,
                'platformVersion' => PHP_VERSION,
                'yggdrasilVersion' => null,
                'specVersion' => Unleash::SPECIFICATION_VERSION,
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request
            ->withHeader('x-unleash-appname', $this->configuration->getAppName())
            ->withHeader('x-unleash-sdk', $this->sdkName . ':' . $this->sdkVersion);

        try {
            $response = $this->httpClient->sendRequest($request);
            $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (Exception) {
            $result = false;
        }

        $this->storeCache($result);

        return $result;
    }

    private function hasValidCacheRegistration(): bool
    {
        $cache = $this->configuration->getCache();
        $staleCache = $this->configuration->getStaleCache();

        $hasNormalCache = $cache->has(CacheKey::REGISTRATION);
        $hasStaleCache = $staleCache->has(CacheKey::REGISTRATION);

        return match (true) {
            $hasNormalCache => (bool) $cache->get(CacheKey::REGISTRATION),
            $hasStaleCache => (bool) $staleCache->get(CacheKey::REGISTRATION),
            default => false,
        };
    }

    private function storeCache(bool $result): void
    {
        $this->configuration->getCache()->set(CacheKey::REGISTRATION, $result, $this->configuration->getTtl());
        $this->configuration->getStaleCache()->set(CacheKey::REGISTRATION, $result, $this->configuration->getStaleTtl());
    }
}
