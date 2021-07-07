<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\Context;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Exception\MissingArgumentException;

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
