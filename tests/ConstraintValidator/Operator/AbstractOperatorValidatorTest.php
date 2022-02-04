<?php

namespace Unleash\Client\Tests\ConstraintValidator\Operator;

use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\ConstraintValidator\Operator\Lists\InListOperatorValidator;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Tests\AbstractHttpClientTest;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class AbstractOperatorValidatorTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

    public function testInvoke()
    {
        $instance = new InListOperatorValidator();
        self::assertFalse($instance('test', 'test'));
        self::assertFalse($instance('test', null));
    }

    public function testMissingValues()
    {
        $unleash = new DefaultUnleash(
            [new DefaultStrategyHandler()],
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', '', ))
                ->setCache($this->getCache())
                ->setMetricsEnabled(false)
                ->setAutoRegistrationEnabled(false),
            new class implements MetricsHandler {
                public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
                {
                }
            },
            new DefaultVariantHandler(new MurmurHashCalculator())
        );

        $this->pushResponse([
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
                                    'contextName' => 'email',
                                    'operator' => ConstraintOperator::STRING_STARTS_WITH,
                                    // missing 'value' and 'values'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($unleash->isEnabled('test'));
    }
}
