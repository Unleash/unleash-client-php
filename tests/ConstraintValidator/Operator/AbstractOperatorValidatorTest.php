<?php

namespace Unleash\Client\Tests\ConstraintValidator\Operator;

use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\ConstraintValidator\Operator\Lists\InListOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringContainsOperatorValidator;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Exception\OperatorValidatorException;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Tests\AbstractHttpClientTest;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class AbstractOperatorValidatorTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

    public function testInvokeNull()
    {
        $instance = new InListOperatorValidator();
        $this->expectException(OperatorValidatorException::class);
        $instance('test', null);
    }

    public function testInvokeUnacceptableType()
    {
        $instance = new InListOperatorValidator();
        $this->expectException(OperatorValidatorException::class);
        $instance('test', 'test');
    }

    public function testInvokeMultipleValuesUnacceptableType()
    {
        $instance = new StringContainsOperatorValidator();
        $this->expectException(OperatorValidatorException::class);
        $instance('test', ['some-value', [], 'test']);
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
                                    'inverted' => true,
                                    // missing 'value' and 'values' and 'inverted' set to true
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($unleash->isEnabled('test'));
        self::assertFalse($unleash->isEnabled('test'));
    }
}
