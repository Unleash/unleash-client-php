<?php

namespace Unleash\Client\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultConstraint;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Exception\MissingArgumentException;
use Unleash\Client\Strategy\UserIdStrategyHandler;

final class UserIdStrategyHandlerTest extends TestCase
{
    public function testSupports()
    {
        $instance = new UserIdStrategyHandler();
        self::assertFalse($instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertTrue($instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        $instance = new UserIdStrategyHandler();
        $context = new UnleashContext('123');

        self::assertFalse($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '123',
        ]), new UnleashContext()));

        try {
            $instance->isEnabled(new DefaultStrategy('userWithId', [
                'userIds' => '',
            ]), new UnleashContext());
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $ignored) {
        }

        self::assertTrue($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '123,456',
        ]), $context));
        self::assertFalse($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '789',
        ]), $context));

        $strategy = new DefaultStrategy('whatever', [
            'userIds' => '123',
        ], [
            new DefaultConstraint('something', ConstraintOperator::IN, ['test']),
        ]);
        self::assertFalse($instance->isEnabled($strategy, new UnleashContext('123')));
        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext('123'))->setCustomProperty('something', 'test')
        ));

        $strategy = new DefaultStrategy('whatever', [
            'userIds' => '123',
        ], [
            new DefaultConstraint('something', ConstraintOperator::NOT_IN, ['test']),
        ]);
        self::assertTrue($instance->isEnabled($strategy, new UnleashContext('123')));
        self::assertFalse($instance->isEnabled(
            $strategy,
            (new UnleashContext('123'))->setCustomProperty('something', 'test')
        ));
    }
}
