<?php

namespace Unleash\Client\Tests\ConstraintValidator;

use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Tests\AbstractHttpClientTest;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class ConstraintTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

    public function testInvalidVersion()
    {
        $instance = new DefaultUnleash(
            [new DefaultStrategyHandler()],
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getCache()),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator()),
        );

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_EQ',
                                    'value' => 'version55',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5');

        self::assertFalse($instance->isEnabled('test', $context));
    }

    public function testInvalidOperator()
    {
        $instance = new DefaultUnleash(
            [new DefaultStrategyHandler()],
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getCache()),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator()),
        );

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'invalid_operator',
                                    'value' => 'version55',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($instance->isEnabled('test'));
    }
}
