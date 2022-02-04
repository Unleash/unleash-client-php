<?php

namespace Unleash\Client\Tests\ConstraintValidator\Operator\Number;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Tests\TestHelpers\AbstractNumberOperatorValidatorImpl73;
use Unleash\Client\Tests\TestHelpers\AbstractNumberOperatorValidatorImpl80;

final class AbstractNumberOperatorValidatorTest extends TestCase
{
    public function testConvert()
    {
        // these tests also run on php < 8 which doesn't support multiple types
        if (PHP_VERSION_ID >= 80000) {
            $instance = new AbstractNumberOperatorValidatorImpl80();
        } else {
            $instance = new AbstractNumberOperatorValidatorImpl73();
        }

        self::assertIsInt($instance->convert('5'));
        self::assertIsFloat($instance->convert('5.0'));
    }
}
