<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use JsonSerializable;
use Psr\Http\Client\ClientInterface;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Helper\Builder\HttpClientAware;

final class HttpClientAwareBootstrapProvider80 implements BootstrapProvider, HttpClientAware
{
    public ?ClientInterface $client = null;

    public function getBootstrap(): JsonSerializable|null|iterable
    {
        return null;
    }

    public function setHttpClient(ClientInterface $client): void
    {
        $this->client = $client;
    }
}
