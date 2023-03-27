<?php

namespace Unleash\Client\Helper\Builder;

use Psr\Http\Message\RequestFactoryInterface;

interface RequestFactoryAware
{
    public function setRequestFactory(RequestFactoryInterface $requestFactory): void;
}
