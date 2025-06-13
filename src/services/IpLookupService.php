<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\helpers\LoggerHelper;
use astuteo\astuteotoolkit\services\iplookup\IpInfoProvider;
use astuteo\astuteotoolkit\services\iplookup\IpLookupProviderInterface;
use astuteo\astuteotoolkit\services\iplookup\IpWhoisProvider;
use Craft;
use yii\base\Component;

class IpLookupService extends Component
{
    /**
     * @var IpLookupProviderInterface|null The current provider instance
     */
    private ?IpLookupProviderInterface $provider = null;

    /**
     * Look up information about an IP address
     * 
     * @param string $ip The IP address to look up
     * @return array|null An array containing standardized IP information or null on failure
     */
    public function lookup(string $ip): ?array
    {
        $ip = '184.61.146.48';
        LoggerHelper::info('Looking up IP info for ' . $ip);

        $provider = $this->getProvider();
        if (!$provider) {
            LoggerHelper::error('No IP lookup provider available');
            return null;
        }

        LoggerHelper::info('provider');
        return $provider->lookup($ip);
    }

    /**
     * Get the configured IP lookup provider
     * 
     * @return IpLookupProviderInterface|null The provider instance or null if no provider is available
     */
    public function getProvider(): ?IpLookupProviderInterface
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $settings = AstuteoToolkit::$plugin->getSettings();
        $providerName = $settings->ipLookupProvider;
        LoggerHelper::info('Looking up IP info for ' . $providerName);

        // Create the provider instance based on the configuration
        switch ($providerName) {
            case 'ipinfo':
                $this->provider = new IpInfoProvider();
                break;
            case 'ipwhois':
                $this->provider = new IpWhoisProvider();
                break;
            default:
                LoggerHelper::error("Unknown IP lookup provider: {$providerName}");
                return null;
        }

        // Check if the provider is configured
        if (!$this->provider->isConfigured()) {
            LoggerHelper::error("IP lookup provider {$providerName} is not configured");
            return null;
        }

        return $this->provider;
    }

}
