<?php

namespace Unleash\Client\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultConstraint;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Strategy\DefaultStrategyHandler;

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
            new DefaultConstraint('something', ConstraintOperator::IN_LIST, ['test']),
        ]);
        self::assertFalse($instance->isEnabled($strategy, new UnleashContext()));
        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));

        $strategy = new DefaultStrategy('whatever', [], [
            new DefaultConstraint('something', ConstraintOperator::NOT_IN_LIST, ['test']),
        ]);
        self::assertTrue($instance->isEnabled($strategy, new UnleashContext()));
        self::assertFalse($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));
    }
}
