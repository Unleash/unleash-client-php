<?php

namespace Unleash\Client\Strategy;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Exception\MissingArgumentException;

final class IpAddressStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @throws MissingArgumentException
     */
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$ipAddresses = $this->findParameter('IPs', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'IPs' config");
        }
        $ipAddresses = array_map('trim', explode(',', $ipAddresses));

        $enabled = in_array($context->getIpAddress(), $ipAddresses, true);

        if (!$enabled) {
            return false;
        }

        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }

        return true;
    }

    public function getStrategyName(): string
    {
        return 'remoteAddress';
    }
}
