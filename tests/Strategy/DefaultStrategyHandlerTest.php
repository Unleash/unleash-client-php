<?php

namespace Rikudou\Tests\Unleash\Strategy;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultConstraint;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\Enum\ConstraintOperator;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;

final class DefaultStrategyHandlerTest extends TestCase
{
    public function testSupports()
    {
        $instance = new DefaultStrategyHandler();
        self::assertTrue($instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        $instance = new DefaultStrategyHandler();
        self::assertTrue($instance->isEnabled(new DefaultStrategy('whatever', []), new UnleashContext()));

        $strategy = new DefaultStrategy('whatever', [], [
            new DefaultConstraint('something', ConstraintOperator::IN, ['test']),
        ]);
        self::assertFalse($instance->isEnabled($strategy, new UnleashContext()));
        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));

        $strategy = new DefaultStrategy('whatever', [], [
            new DefaultConstraint('something', ConstraintOperator::NOT_IN, ['test']),
        ]);
        self::assertTrue($instance->isEnabled($strategy, new UnleashContext()));
        self::assertFalse($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));
    }
}
