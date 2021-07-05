<?php

namespace Rikudou\Tests\Unleash\Strategy;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultConstraint;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\Enum\ConstraintOperator;
use Rikudou\Unleash\Exception\MissingArgumentException;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;

final class IpAddressStrategyHandlerTest extends TestCase
{
    public function testSupports()
    {
        $instance = new IpAddressStrategyHandler();
        self::assertFalse($instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertTrue($instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        $context = new UnleashContext(null, '127.0.0.1');

        $instance = new IpAddressStrategyHandler();

        self::assertFalse($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '127.0.0.1',
        ]), new UnleashContext()));
        self::assertTrue($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '192.168.0.1',
        ]), new UnleashContext()));

        self::assertTrue($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '127.0.0.1',
        ]), $context));
        self::assertFalse($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '192.168.0.1',
        ]), $context));

        $strategy = new DefaultStrategy('whatever', [
            'IPs' => '192.168.0.1',
        ], [
            new DefaultConstraint('something', ConstraintOperator::IN, ['test']),
        ]);
        self::assertFalse($instance->isEnabled($strategy, new UnleashContext()));
        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));

        $strategy = new DefaultStrategy('whatever', [
            'IPs' => '192.168.0.1',
        ], [
            new DefaultConstraint('something', ConstraintOperator::NOT_IN, ['test']),
        ]);
        self::assertTrue($instance->isEnabled($strategy, new UnleashContext()));
        self::assertFalse($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));

        $this->expectException(MissingArgumentException::class);
        $instance->isEnabled(new DefaultStrategy('remoteAddress', []), $context);
    }

    public function testEmptyIpAddresses()
    {
        $context = new UnleashContext(null, '127.0.0.1');
        $instance = new IpAddressStrategyHandler();

        $this->expectException(MissingArgumentException::class);
        $instance->isEnabled(new DefaultStrategy('', [
            'IPs' => '',
        ]), $context);
    }
}
