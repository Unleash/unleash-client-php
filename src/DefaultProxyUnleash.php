<?php

namespace Unleash\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultProxyFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\ProxyFeature;
use Unleash\Client\DTO\ProxyVariant;
use Unleash\Client\DTO\DefaultProxyVariant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Metrics\MetricsHandler;

final class DefaultProxyUnleash implements ProxyUnleash
{
    public function __construct(
        private string $baseUrl,
        private UnleashConfiguration $configuration,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private MetricsHandler $metricsHandler,
        private ?CacheInterface $cache = null,
    ) {
        $this->baseUrl = $baseUrl;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
        $this->cache = $cache;
        $this->metricsHandler = $metricsHandler;
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $response = $this->resolveSingleToggle($featureName, $context);
        $enabled = $response ? $response->isEnabled() : $default;
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $enabled, []), $enabled);

        return $enabled;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?ProxyVariant $fallbackVariant = null): ProxyVariant
    {
        $variant = $fallbackVariant ?? new DefaultProxyVariant('disabled', false, null);

        $response = $this->resolveSingleToggle($featureName, $context);

        if ($response !== null) {
            $variant = $response->getVariant();
        }
        $metricVariant = new DefaultVariant($variant->getName(), $variant->isEnabled(), 0, Stickiness::DEFAULT , $variant->getPayload());
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $variant->isEnabled(), []), $variant->isEnabled(), $metricVariant);

        return $variant;
    }

    private function resolveSingleToggle(string $featureName, ?Context $context = null): ?ProxyFeature
    {
        if ($this->cache !== null && $this->cache->has($featureName)) {
            $cachedFeature = $this->cache->get($featureName);
            if (is_array($cachedFeature)) {
                $featureData = $this->validateResponse($cachedFeature);
                if ($featureData !== null) {
                    return new DefaultProxyFeature($featureData);
                }
            }
        }

        $context ??= new UnleashContext();
        $featureUrl = $this->baseUrl . '/features/' . $featureName;
        $url = $this->addQuery($featureUrl, $this->contextToQueryString($context));

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = $this->httpClient->sendRequest($request);
        $body = json_decode($response->getBody(), true);
        if ($body === null) {
            return null;
        }
        if (is_array($body)) {

            $featureData = $this->validateResponse($body);

            if ($featureData !== null) {
                if ($this->cache !== null) {
                    $this->cache->set($featureName, $featureData);
                }

                return new DefaultProxyFeature($featureData);
            }
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

        $query['properties'] = [];

        foreach ($properties as $key => $value) {
            $query['properties'][$key] = $value;
        }

        return urldecode(http_build_query($query));
    }

    private function addQuery(string $url, string $query): string
    {
        $urlParts = parse_url($url);

        if ($urlParts === false) {
            throw new \InvalidArgumentException('Invalid URL provided');
        }

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

    /**
     * @param array<string, mixed> $response
     * @return array{
     *     name: string,
     *     enabled: bool,
     *     variant: array{
     *         name: string,
     *         enabled: bool,
     *         payload?: array{
     *             type: string,
     *             value: string
     *         }
     *     },
     *     impression_data: bool
     * }|null
     */
    private function validateResponse(array $response): ?array
    {
        if (!isset($response['name'], $response['enabled'], $response['variant'], $response['impression_data'])) {
            return null;
        }

        if (!is_string($response['name']) || !is_bool($response['enabled']) || !is_bool($response['impression_data']) || !is_array($response['variant'])) {
            return null;
        }

        if (!isset($response['variant']['name'], $response['variant']['enabled']) || !is_string($response['variant']['name']) || !is_bool($response['variant']['enabled'])) {
            return null;
        }

        if (isset($response['variant']['payload']) && (!is_array($response['variant']['payload']) || !isset($response['variant']['payload']['type'], $response['variant']['payload']['value']) || !is_string($response['variant']['payload']['type']) || !is_string($response['variant']['payload']['value']))) {
            return null;
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $parts
     * @return string
     */
    private function buildUrl(array $parts): string
    {
        $result = '';
        if (isset($parts['scheme']) && is_string($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }
        if (isset($parts['user']) && is_string($parts['user'])) {
            $result .= $parts['user'];
            if (isset($parts['pass']) && is_string($parts['pass'])) {
                $result .= ":" . $parts['pass'];
            }
            $result .= '@';
        }
        if (isset($parts['host']) && is_string($parts['host'])) {
            $result .= $parts['host'];
        }
        if (isset($parts['port']) && is_numeric($parts['port'])) {
            $result .= ":" . $parts['port'];
        }
        if (isset($parts['path']) && is_string($parts['path'])) {
            $result .= $parts['path'];
        }
        if (isset($parts['query']) && is_string($parts['query'])) {
            $result .= "?" . $parts['query'];
        }
        if (isset($parts['fragment']) && is_string($parts['fragment'])) {
            $result .= "#" . $parts['fragment'];
        }

        return $result;
    }
}