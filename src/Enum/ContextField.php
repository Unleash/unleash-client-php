<?php

namespace Unleash\Client\Enum;

final class ContextField
{
    public const USER_ID = 'userId';

    public const SESSION_ID = 'sessionId';

    public const IP_ADDRESS = 'remoteAddress';

    public const ENVIRONMENT = 'environment';

    public const REMOTE_ADDRESS = self::IP_ADDRESS;

    public const HOSTNAME = 'hostname';

    public const CURRENT_TIME = 'currentTime';
}
