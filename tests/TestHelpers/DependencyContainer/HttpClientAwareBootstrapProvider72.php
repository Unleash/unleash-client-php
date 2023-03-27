<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use JsonSerializable;
use Psr\Http\Client\ClientInterface;
use Traversable;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Helper\Builder\HttpClientAware;

final class HttpClientAwareBootstrapProvider72 implements BootstrapProvider, HttpClientAware
{
    public ?ClientInterface $client = null;

    public function getBootstrap(): array|JsonSerializable|Traversable|null
    {
        return null;
    }

    public function setHttpClient(ClientInterface $client): void
    {
        $this->client = $client;
    }
}
