<?php

namespace Rikudou\Tests\Unleash\Strategy;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\Exception\MissingArgumentException;
use Rikudou\Unleash\Strategy\UserIdStrategyHandler;

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
    }
}
