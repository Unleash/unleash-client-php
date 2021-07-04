<?php

namespace Rikudou\Tests\Unleash\Configuration;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\Enum\ContextField;
use Rikudou\Unleash\Exception\InvalidValueException;

final class UnleashContextTest extends TestCase
{
    public function testCustomProperties()
    {
        $context = new UnleashContext();

        self::assertFalse($context->hasCustomProperty('test'));

        $context->setCustomProperty('test', 'test');
        self::assertTrue($context->hasCustomProperty('test'));
        self::assertEquals('test', $context->getCustomProperty('test'));

        $context->setCustomProperty('test', 'test2');
        self::assertEquals('test2', $context->getCustomProperty('test'));

        $context->removeCustomProperty('test');
        self::assertFalse($context->hasCustomProperty('test'));

        $this->expectException(InvalidValueException::class);
        $context->getCustomProperty('test');
    }

    public function testCustomPropertyRemoval()
    {
        $context = new UnleashContext();
        $context->removeCustomProperty('nonexistent');

        $this->expectException(InvalidValueException::class);
        $context->removeCustomProperty('nonexistent', false);
    }

    public function testFindContextValue()
    {
        unset($_SERVER['REMOTE_ADDR']);

        $context = (new UnleashContext('123', '456', '789'))
            ->setCustomProperty('someField', '012');
        self::assertEquals('123', $context->findContextValue(ContextField::USER_ID));
        self::assertEquals('456', $context->findContextValue(ContextField::IP_ADDRESS));
        self::assertEquals('789', $context->findContextValue(ContextField::SESSION_ID));
        self::assertEquals('012', $context->findContextValue('someField'));
        self::assertNull($context->findContextValue('someOtherField'));

        $context = new UnleashContext();
        self::assertNull($context->findContextValue(ContextField::USER_ID));
        self::assertNull($context->findContextValue(ContextField::IP_ADDRESS));
        self::assertNull($context->findContextValue(ContextField::SESSION_ID));
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        self::assertEquals('127.0.0.1', $context->findContextValue(ContextField::IP_ADDRESS));
    }

    public function testHasMatchingFieldValue()
    {
        unset($_SERVER['REMOTE_ADDR']);

        $context = (new UnleashContext('123', '456', '789'))
            ->setCustomProperty('someField', '012');

        self::assertTrue($context->hasMatchingFieldValue(ContextField::USER_ID, [
            '123',
            '789',
        ]));
        self::assertTrue($context->hasMatchingFieldValue(ContextField::IP_ADDRESS, [
            '741',
            '456',
        ]));
        self::assertTrue($context->hasMatchingFieldValue(ContextField::SESSION_ID, [
            '852',
            '789',
        ]));
        self::assertTrue($context->hasMatchingFieldValue('someField', [
            '753',
            '012',
        ]));

        self::assertFalse($context->hasMatchingFieldValue(ContextField::USER_ID, []));
        self::assertFalse($context->hasMatchingFieldValue('nonexistent', ['someValue']));
    }
}
