<?php

namespace Unleash\Client\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutRandomStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutSessionIdStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutUserIdStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class ClientSpecificationTest extends AbstractHttpClientTestCase
{
    use RealCacheImplementationTrait;

    public function testClientSpecifications()
    {
        $stickinessCalculator = new MurmurHashCalculator();
        $gradualRolloutStrategy = new GradualRolloutStrategyHandler($stickinessCalculator);

        $configuration = (new UnleashConfiguration('', '', ''))
            ->setAutoRegistrationEnabled(false)
            ->setCache($this->getCache());
        $this->repository = new DefaultUnleashRepository(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );

        $unleash = new DefaultUnleash(
            [
            new DefaultStrategyHandler(),
            $gradualRolloutStrategy,
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            new GradualRolloutUserIdStrategyHandler($gradualRolloutStrategy),
            new GradualRolloutSessionIdStrategyHandler($gradualRolloutStrategy),
            new GradualRolloutRandomStrategyHandler($gradualRolloutStrategy),
        ],
            $this->repository,
            $this->registrationService,
            $configuration,
            new class implements MetricsHandler {
                public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
                {
                }
            },
            new DefaultVariantHandler($stickinessCalculator)
        );

        $specificationList = $this->getJson('index.json');

        $disabledFeatureTests = [];

        foreach ($specificationList as $specificationFilename) {
            if (in_array($specificationFilename, $disabledFeatureTests, true)) {
                continue;
            }
            $specificationConfig = $this->getJson($specificationFilename);
            foreach ($specificationConfig['tests'] ?? [] as $test) {
                $this->pushResponse($specificationConfig['state']);
                self::assertEquals(
                    $test['expectedResult'],
                    $unleash->isEnabled($test['toggleName'], $this->createContext($test['context'])),
                    $test['description']
                );
                $configuration->setCache($this->getFreshCacheInstance());
            }

            foreach ($specificationConfig['variantTests'] ?? [] as $variantTest) {
                $this->pushResponse($specificationConfig['state']);
                self::assertEquals(
                    $variantTest['expectedResult'],
                    $unleash
                        ->getVariant($variantTest['toggleName'], $this->createContext($variantTest['context']))
                        ->jsonSerialize(),
                    $variantTest['description']
                );
                $configuration->setCache($this->getFreshCacheInstance());
            }
        }
    }

    private function getJson(string $filename): array
    {
        return json_decode(
            file_get_contents(__DIR__ . "/client-specification/specifications/{$filename}"),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function createContext(array $context): UnleashContext
    {
        $contextObject = (new UnleashContext())
            ->setCurrentUserId($context['userId'] ?? null)
            ->setSessionId($context['sessionId'] ?? null)
            ->setEnvironment($context['environment'] ?? null)
            ->setIpAddress($context['remoteAddress'] ?? '');

        if (isset($context['properties'])) {
            foreach ($context['properties'] as $property => $value) {
                $contextObject->setCustomProperty($property, $value);
            }
        }

        foreach ($context as $key => $value) {
            if ($key === 'properties') {
                continue;
            }
            $contextObject->setCustomProperty($key, $value);
        }

        return $contextObject;
    }
}
