<?php

namespace Unleash\Client\Enum;

final class ContextField
{
    public const string USER_ID = 'userId';

    public const string SESSION_ID = 'sessionId';

    public const string IP_ADDRESS = 'remoteAddress';

    public const string ENVIRONMENT = 'environment';

    public const string REMOTE_ADDRESS = self::IP_ADDRESS;

    public const string HOSTNAME = 'hostname';

    public const string CURRENT_TIME = 'currentTime';
}
