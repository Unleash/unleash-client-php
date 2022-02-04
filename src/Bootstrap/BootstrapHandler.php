<?php

namespace Unleash\Client\Bootstrap;

interface BootstrapHandler
{
    public function getBootstrapContents(BootstrapProvider $provider): ?string;
}
