<?php

namespace Unleash\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\ProxyVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\DTO\DefaultProxyVariant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Metrics\MetricsHandler;

final class DefaultProxyUnleash implements ProxyUnleash
{
    public function __construct(
        private string $url,
        private UnleashConfiguration $configuration,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private ?CacheInterface $cache = null,
        private ?MetricsHandler $metricsHandler = null,
    ) {
        $this->url = $url . '/features';
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
        $this->cache = $cache;
        $this->metricsHandler = $metricsHandler;
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $body = $this->fetchFromApi($featureName, $context);
        $enabled = $body['enabled'] ?? false;
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $enabled, []), $enabled);
        return $enabled;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?ProxyVariant $fallbackVariant = null): ProxyVariant
    {
        $variant = $fallbackVariant ?? new DefaultProxyVariant('disabled', false, null);

        $body = $this->fetchFromApi($featureName, $context);

        if ($body !== null) {
            $payload = null;

            if (isset($body['variant']['payload']['type']) && isset($body['variant']['payload']['value'])) {
                $payload = new DefaultVariantPayload($body['variant']['payload']['type'], $body['variant']['payload']['value']);
            }

            if (isset($body['variant'], $body['variant']['name'], $body['variant']['enabled'])) {
                $variant = new DefaultProxyVariant($body['variant']['name'], $body['variant']['enabled'], $payload);
            }
        }

        $metricVariant = new DefaultVariant($variant->getName(), $variant->isEnabled(), 0, Stickiness::DEFAULT , $variant->getPayload());
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $variant->isEnabled(), []), $variant->isEnabled(), $metricVariant);

        return $variant;
    }

    private function fetchFromApi(string $featureName, ?Context $context = null): ?array
    {
        if ($this->cache !== null && $this->cache->has($featureName)) {
            return $this->cache->get($featureName);
        }

        $context ??= new UnleashContext();
        $featureUrl = $this->url . '/' . $featureName;
        $url = $this->addQuery($featureUrl, $this->contextToQueryString($context));

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = $this->httpClient->sendRequest($request);
        $body = json_decode($response->getBody(), true);

        if (isset($body['name'], $body['enabled'], $body['variant']) && $body['name'] === $featureName) {
            if ($this->cache !== null) {
                $this->cache->set($featureName, $body);
            }

            return $body;
        }

        return null;
    }

    private function contextToQueryString(Context $context): string
    {
        $query = [];
        $values = [
            'userId' => $context->getCurrentUserId(),
            'sessionId' => $context->getSessionId(),
            'remoteAddress' => $context->getIpAddress(),
        ];

        $values = array_filter($values, fn(?string $value) => $value !== null);
        $properties = array_filter($context->getCustomProperties(), fn(?string $value) => $value !== null);

        foreach ($values as $key => $value) {
            $query[$key] = $value;
        }
        foreach ($properties as $key => $value) {
            $query['properties'] ??= [];
            $query['properties'][$key] = $value;
        }

        return urldecode(http_build_query($query));
    }

    private function addQuery(string $url, string $query): string
    {
        $urlParts = parse_url($url);
        if (!isset($urlParts['query'])) {
            $urlParts['query'] = $query;
        } else {
            parse_str($urlParts['query'], $existingQuery);
            parse_str($query, $newQuery);
            $merged = array_merge($existingQuery, $newQuery);
            $urlParts['query'] = http_build_query($merged);
        }

        return $this->buildUrl($urlParts);
    }

    private function buildUrl(array $parts): string
    {
        $result = '';
        if (isset($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $result .= $parts['user'];
            if (isset($parts['pass'])) {
                $result .= ":{$parts['pass']}";
            }
            $result .= '@';
        }
        if (isset($parts['host'])) {
            $result .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $result .= ":{$parts['port']}";
        }
        if (isset($parts['path'])) {
            $result .= $parts['path'];
        }
        if (isset($parts['query'])) {
            $result .= "?{$parts['query']}";
        }
        if (isset($parts['fragment'])) {
            $result .= "#{$parts['fragment']}";
        }

        return $result;
    }
}