<?php

namespace Unleash\Client\Tests\ConstraintValidator\Operator\Date;

use PHPUnit\Framework\TestCase;
use Unleash\Client\ConstraintValidator\Operator\Date\AbstractDateOperatorValidator;

final class AbstractDateOperatorValidatorTest extends TestCase
{
    public function testAcceptValues()
    {
        $instance = new class extends AbstractDateOperatorValidator {
            public function acceptsValues($values): bool
            {
                return parent::acceptsValues($values);
            }

            protected function validate(string $currentValue, $searchInValue): bool
            {
                return false;
            }
        };

        self::assertFalse($instance->acceptsValues([]));
        self::assertFalse($instance->acceptsValues('invalid-date'));
        self::assertTrue($instance->acceptsValues('2022-02-04T19:14:49+00:00'));
    }
}
