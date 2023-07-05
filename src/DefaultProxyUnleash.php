<?php

namespace Unleash\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\ProxyVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\DTO\DefaultProxyVariant;

final class DefaultProxyUnleash implements ProxyUnleash
{
    public function __construct(private string $url, private string $apiKey, private ClientInterface $httpClient, private RequestFactoryInterface $requestFactory)
    {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $context ??= new UnleashContext();
        $url = $this->addQuery($this->url, $this->contextToQueryString($context));

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Authorization', $this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        $response = $this->httpClient->sendRequest($request);
        $body = json_decode($response->getBody(), true);
        return $body['enabled'] ?? false;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?ProxyVariant $fallbackVariant = null): ProxyVariant
    {
        $context ??= new UnleashContext();
        $url = $this->addQuery($this->url, $this->contextToQueryString($context));

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Authorization', $this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        $response = $this->httpClient->sendRequest($request);
        $body = json_decode($response->getBody(), true);
        $payload = null;

        if (isset($body['variant']['payload']['type']) && isset($body['variant']['payload']['value'])) {
            $payload = new DefaultVariantPayload($body['variant']['payload']['type'], $body['variant']['payload']['value']);
        }

        if (isset($body['variant']) && isset($body['variant']['name']) && isset($body['variant']['enabled'])) {
            return new DefaultProxyVariant($body['variant']['name'], $body['variant']['enabled'], $payload);
        } else {
            return $fallbackVariant ?? new DefaultProxyVariant('disabled', false, null);
        }
    }

    public function register(): bool
    {
        return false;
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