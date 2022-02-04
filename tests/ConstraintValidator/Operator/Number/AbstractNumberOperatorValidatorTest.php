<?php

namespace Unleash\Client\Tests\ConstraintValidator\Operator\Number;

use PHPUnit\Framework\TestCase;
use Unleash\Client\ConstraintValidator\Operator\Number\AbstractNumberOperatorValidator;

final class AbstractNumberOperatorValidatorTest extends TestCase
{
    public function testConvert()
    {
        $instance = new class extends AbstractNumberOperatorValidator {
            protected function validate(string $currentValue, array|string $searchInValue): bool
            {
                return false;
            }

            public function convert(string $number): int|float
            {
                return parent::convert($number);
            }
        };

        self::assertIsInt($instance->convert('5'));
        self::assertIsFloat($instance->convert('5.0'));
    }
}
