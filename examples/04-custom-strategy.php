<?php

/**
 * Here we are creating a kill switch maintenance strategy which allows access from whitelisted IP addresses,
 * this might be slightly more useful than the April Fools strategy from README.
 */

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Strategy\AbstractStrategyHandler;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;
use Rikudou\Unleash\UnleashBuilder;

require __DIR__ . '/_common.php';

final class MaintenanceKillSwitchStrategyHandler extends AbstractStrategyHandler
{
    /**
     * Let's reuse the IP address strategy because whitelisting IPs is pretty much the opposite of that
     */
    public function __construct(private IpAddressStrategyHandler $ipAddressStrategyHandler)
    {
    }

    public function getStrategyName(): string
    {
        return 'killSwitch';
    }

    /**
     * We could implement the logic ourselves or we can reuse the existing strategy and return the reverse.
     *
     * Note that if the kill switch toggle is disabled in Unleash it won't even get here and thus in this method
     * we can always assume the toggle is enabled.
     */
    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        // assume we named our parameter with whitelisted IPs as ipAddresses
        $whitelistIpAddresses = $this->findParameter('ipAddresses', $strategy);
        if ($whitelistIpAddresses === null) {
            // kill switch is the reverse of usual feature, meaning if no ip address is defined, assume the kill
            // switch is active
            return true;
        }

        /**
         * Here we create a strategy DTO that will be passed to IpAddressStrategyHandler which expects the list of
         * IPs in the "IPs" parameter. If you want this kill switch to ignore constraints, you can just not pass
         * them to the transformed strategy
         */
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'IPs' => $whitelistIpAddresses,
            ],
            $strategy->getConstraints()
        );

        // here we return the reverse result of ip address strategy handler because we want any whitelisted ip address
        // to return false meaning the kill switch is not enabled for them
        return !$this->ipAddressStrategyHandler->isEnabled($transformedStrategy, $context);
    }
}

$unleash = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withHeader('Authorization', $apiKey)
    ->withStrategy(new KillSwitchStrategyHandler(new IpAddressStrategyHandler())) // add our custom strategy
    ->build();

if ($unleash->isEnabled('myAppKillSwitch')) {
    echo "Kill switch is enabled, exiting", PHP_EOL;
    exit();
}

echo "Kill switch not enabled, cool!", PHP_EOL;
