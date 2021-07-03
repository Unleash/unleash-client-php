<?php

namespace Rikudou\Unleash\Client;

use DateTimeImmutable;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Helper\StringStream;
use Rikudou\Unleash\Strategy\StrategyHandler;
use Rikudou\Unleash\Unleash;

final class DefaultRegistrationService implements RegistrationService
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UnleashConfiguration $configuration,
        private array $headers
    ) {
    }

    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     *
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function register(iterable $strategyHandlers): bool
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->configuration->getUrl() . 'client/register')
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'sdkVersion' => 'rikudou-unleash-sdk:' . Unleash::SDK_VERSION,
                'strategies' => array_map(function (StrategyHandler $strategyHandler): string {
                    return $strategyHandler->getStrategyName();
                }, [...$strategyHandlers]),
                'started' => (new DateTimeImmutable())->format('c'),
                'interval' => 10_000,
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $response = $this->httpClient->sendRequest($request);

        return $response->getStatusCode() === 202;
    }
}
