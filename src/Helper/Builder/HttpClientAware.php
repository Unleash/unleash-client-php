<?php

namespace Unleash\Client\Helper\Builder;

use Psr\Http\Client\ClientInterface;

interface HttpClientAware
{
    public function setHttpClient(ClientInterface $client): void;
}
