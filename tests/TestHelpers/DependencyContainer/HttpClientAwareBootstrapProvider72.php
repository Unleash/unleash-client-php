<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Psr\Http\Client\ClientInterface;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Helper\Builder\HttpClientAware;

final class HttpClientAwareBootstrapProvider72 implements BootstrapProvider, HttpClientAware
{
    public ?ClientInterface $client = null;

    public function getBootstrap()
    {
        return null;
    }

    public function setHttpClient(ClientInterface $client): void
    {
        $this->client = $client;
    }
}
