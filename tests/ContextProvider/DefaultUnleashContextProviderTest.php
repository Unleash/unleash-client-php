<?php

namespace Unleash\Client\Tests\ContextProvider;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;

final class DefaultUnleashContextProviderTest extends TestCase
{
    public function testGetContext()
    {
        unset($_SERVER['REMOTE_ADDR']);

        $instance = new DefaultUnleashContextProvider();
        self::assertInstanceOf(UnleashContext::class, $instance->getContext());
        self::assertNull($instance->getContext()->getCurrentUserId());
        self::assertNull($instance->getContext()->getIpAddress());
        self::assertNull($instance->getContext()->getSessionId());
        self::assertNull($instance->getContext()->getEnvironment());
        self::assertNotSame($instance->getContext(), $instance->getContext());
    }
}
