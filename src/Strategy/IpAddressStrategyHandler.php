<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Exception\MissingArgumentException;

final class IpAddressStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @throws MissingArgumentException
     */
    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        if (!$ipAddresses = $this->findParameter('IPs', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'IPs' config");
        }
        $ipAddresses = array_map('trim', explode(',', $ipAddresses));

        return in_array($context->getIpAddress(), $ipAddresses, true);
    }

    protected function getStrategyName(): string
    {
        return 'remoteAddress';
    }
}
