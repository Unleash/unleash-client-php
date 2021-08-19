<?php

namespace Unleash\Client\Strategy;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;

final class ApplicationHostnameStrategyHandler extends AbstractStrategyHandler
{
    public function getStrategyName(): string
    {
        return 'applicationHostname';
    }

    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$hostnames = $this->findParameter('hostNames', $strategy)) {
            return false;
        }

        $hostnames = array_map('trim', explode(',', $hostnames));
        // todo refactor once rector fixes a bug that prevents phpdoc methods from working
        if (method_exists($context, 'getHostname')) {
            $hostname = $context->getHostname();
        } else {
            // @codeCoverageIgnoreStart
            $hostname = $context->findContextValue('hostname');
            // @codeCoverageIgnoreEnd
        }
        $enabled = in_array($hostname, $hostnames, true);

        if (!$enabled) {
            return false;
        }

        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }

        return true;
    }
}
