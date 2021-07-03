<?php

namespace Rikudou\Tests\Unleash;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DefaultUnleash;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutRandomStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutSessionIdStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutUserIdStrategyHandler;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;
use Rikudou\Unleash\Strategy\UserIdStrategyHandler;

final class ClientSpecificationTest extends AbstractHttpClientTest
{
    public function testClientSpecifications()
    {
        $unleash = new DefaultUnleash([
            new DefaultStrategyHandler(),
            new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            new GradualRolloutUserIdStrategyHandler(new GradualRolloutStrategyHandler(new MurmurHashCalculator())),
            new GradualRolloutSessionIdStrategyHandler(new GradualRolloutStrategyHandler(new MurmurHashCalculator())),
            new GradualRolloutRandomStrategyHandler(new GradualRolloutStrategyHandler(new MurmurHashCalculator())),
        ], $this->repository, $this->registrationService);

        $specificationList = $this->getJson('index.json');

        $disabledFeatureTests = [
            '08-variants.json',
            '09-strategy-constraints.json',
            '11-strategy-constraints-edge-cases.json',
            '12-custom-stickiness.json',
        ];

        foreach ($specificationList as $specificationFilename) {
            if (in_array($specificationFilename, $disabledFeatureTests, true)) {
                continue;
            }
            $specificationConfig = $this->getJson($specificationFilename);
            foreach ($specificationConfig['tests'] as $test) {
                $this->pushResponse($specificationConfig['state']);
                self::assertEquals(
                    $test['expectedResult'],
                    $unleash->isEnabled($test['toggleName'], $this->createContext($test['context'])),
                    $test['description']
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
            ->setIpAddress($context['remoteAddress'] ?? '');

        if (isset($context['properties'])) {
            foreach ($context['properties'] as $property => $value) {
                $contextObject->setCustomProperty($property, $value);
            }
        }

        return $contextObject;
    }
}
