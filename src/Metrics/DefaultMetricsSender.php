<?php

namespace Unleash\Client\Metrics;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Helper\StringStream;

final class DefaultMetricsSender implements MetricsSender
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UnleashConfiguration $configuration,
    ) {
    }

    public function sendMetrics(MetricsBucket $bucket): void
    {
        if (!$this->configuration->isMetricsEnabled() || !$this->configuration->isFetchingEnabled()) {
            return;
        }

        $request = $this->requestFactory
            ->createRequest('POST', $this->configuration->getUrl() . 'client/metrics')
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'bucket' => $bucket->jsonSerialize(),
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $this->httpClient->sendRequest($request);
    }
}
