<?php

namespace Unleash\Client\Enum;

use JetBrains\PhpStorm\Deprecated;

final class ConstraintOperator
{
    // legacy
    #[Deprecated('Please use IN_LIST constant')]
    public const string IN = self::IN_LIST;

    #[Deprecated('Please use NOT_IN_LIST constant')]
    public const string NOT_IN = self::NOT_IN_LIST;

    // list
    public const string IN_LIST = 'IN';

    public const string NOT_IN_LIST = 'NOT_IN';

    // string
    public const string STRING_STARTS_WITH = 'STR_STARTS_WITH';

    public const string STRING_ENDS_WITH = 'STR_ENDS_WITH';

    public const string STRING_CONTAINS = 'STR_CONTAINS';

    // number
    public const string NUMBER_EQUALS = 'NUM_EQ';

    public const string NUMBER_GREATER_THAN = 'NUM_GT';

    public const string NUMBER_GREATER_THAN_OR_EQUALS = 'NUM_GTE';

    public const string NUMBER_LOWER_THAN = 'NUM_LT';

    public const string NUMBER_LOWER_THAN_OR_EQUALS = 'NUM_LTE';

    // date
    public const string DATE_AFTER = 'DATE_AFTER';

    public const string DATE_BEFORE = 'DATE_BEFORE';

    // versions
    public const string VERSION_EQUALS = 'SEMVER_EQ';

    public const string VERSION_GREATER_THAN = 'SEMVER_GT';

    public const string VERSION_LOWER_THAN = 'SEMVER_LT';
}
