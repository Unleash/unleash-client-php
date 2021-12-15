<?php

namespace Unleash\Client\Tests;

use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\AbstractStrategyHandler;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class ClientSpecificationTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

    public function testClientSpecifications()
    {
        $unleash = new DefaultUnleash(
            [
                ...[
                    new DefaultStrategyHandler(),
                    new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
                    new IpAddressStrategyHandler(),
                    new UserIdStrategyHandler(),
                ],
                ...$this->getDeprecatedStrategies(),
            ],
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getCache()),
            new class implements MetricsHandler {
                public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
                {
                }
            },
            new DefaultVariantHandler(new MurmurHashCalculator())
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

    /**
     * The deprecated strategies are moved here as anonymous classes temporarily until references to them
     * are removed from upstream.
     */
    private function getDeprecatedStrategies(): iterable
    {
        $rolloutStrategy = new GradualRolloutStrategyHandler(new MurmurHashCalculator());

        yield new class($rolloutStrategy) extends AbstractStrategyHandler {
            public function __construct(private readonly GradualRolloutStrategyHandler $rolloutStrategyHandler)
            {
            }

            public function isEnabled(Strategy $strategy, Context $context): bool
            {
                $transformedStrategy = new DefaultStrategy(
                    $this->getStrategyName(),
                    [
                        'stickiness' => Stickiness::RANDOM,
                        'groupId' => $strategy->getParameters()['groupId'] ?? '',
                        'rollout' => $strategy->getParameters()['percentage'],
                    ]
                );

                return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
            }

            public function getStrategyName(): string
            {
                return 'gradualRolloutRandom';
            }
        };

        yield new class($rolloutStrategy) extends AbstractStrategyHandler {
            public function __construct(private readonly GradualRolloutStrategyHandler $rolloutStrategyHandler)
            {
            }

            public function isEnabled(Strategy $strategy, Context $context): bool
            {
                $transformedStrategy = new DefaultStrategy(
                    $this->getStrategyName(),
                    [
                        'stickiness' => Stickiness::SESSION_ID,
                        'groupId' => $strategy->getParameters()['groupId'],
                        'rollout' => $strategy->getParameters()['percentage'],
                    ]
                );

                return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
            }

            public function getStrategyName(): string
            {
                return 'gradualRolloutSessionId';
            }
        };

        yield new class($rolloutStrategy) extends AbstractStrategyHandler {
            public function __construct(private readonly GradualRolloutStrategyHandler $rolloutStrategyHandler)
            {
            }

            public function isEnabled(Strategy $strategy, Context $context): bool
            {
                $transformedStrategy = new DefaultStrategy(
                    $this->getStrategyName(),
                    [
                        'stickiness' => Stickiness::USER_ID,
                        'groupId' => $strategy->getParameters()['groupId'],
                        'rollout' => $strategy->getParameters()['percentage'],
                    ]
                );

                return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
            }

            public function getStrategyName(): string
            {
                return 'gradualRolloutUserId';
            }
        };
    }
}
