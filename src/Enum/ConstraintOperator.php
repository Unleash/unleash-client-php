<?php

namespace Unleash\Client\Enum;

use JetBrains\PhpStorm\Deprecated;

final class ConstraintOperator
{
    // legacy
    #[Deprecated('Please use IN_LIST constant')]
    public const IN = self::IN_LIST;

    #[Deprecated('Please use NOT_IN_LIST constant')]
    public const NOT_IN = self::NOT_IN_LIST;

    // list
    public const IN_LIST = 'IN';

    public const NOT_IN_LIST = 'NOT_IN';

    // string
    public const STRING_STARTS_WITH = 'STR_STARTS_WITH';

    public const STRING_ENDS_WITH = 'STR_ENDS_WITH';

    public const STRING_CONTAINS = 'STR_CONTAINS';

    // number
    public const NUMBER_EQUALS = 'NUM_EQ';

    public const NUMBER_GREATER_THAN = 'NUM_GT';

    public const NUMBER_GREATER_THAN_OR_EQUALS = 'NUM_GTE';

    public const NUMBER_LOWER_THAN = 'NUM_LT';

    public const NUMBER_LOWER_THAN_OR_EQUALS = 'NUM_LTE';

    // date
    public const DATE_AFTER = 'DATE_AFTER';

    public const DATE_BEFORE = 'DATE_BEFORE';

    // versions
    public const VERSION_EQUALS = 'SEMVER_EQ';

    public const VERSION_GREATER_THAN = 'SEMVER_GT';

    public const VERSION_LOWER_THAN = 'SEMVER_LT';
}
