<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\helpers\LoggerHelper;
use astuteo\astuteotoolkit\services\iplookup\IpInfoProvider;
use astuteo\astuteotoolkit\services\iplookup\IpLookupProviderInterface;
use astuteo\astuteotoolkit\services\iplookup\IpWhoisProvider;
use craft\helpers\App;
use yii\base\Component;

class IpLookupService extends Component
{
    /**
     * Cache key prefix for all IP lookup cache entries
     */
    const CACHE_KEY_PREFIX = 'astuteo-toolkit-ip-lookup';

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
        $simulateIp = AstuteoToolkit::$plugin->getSettings()->getDevIpAddress();
        if (App::devMode() && $simulateIp) {
            $ip = $simulateIp;
        }

        // Generate a unique cache key for this IP
        $cacheKey = self::CACHE_KEY_PREFIX . '-' . $ip;

        // Try to get the cached result
        $cachedResult = \Craft::$app->cache->get($cacheKey);
        if ($cachedResult !== false) {
            LoggerHelper::warning('Retrieved cached IP info for ' . $ip);
            return $cachedResult;
        }


        $provider = $this->getProvider();
        if (!$provider) {
            LoggerHelper::error('No IP lookup provider available');
            return null;
        }

        $result = $provider->lookup($ip);

        // Cache the result for 60 days
        if ($result !== null) {
            \Craft::$app->cache->set($cacheKey, $result, 60 * 24 * 60 * 60); // 60 days in seconds
            LoggerHelper::info('Cached IP info for ' . $ip);
        }

        return $result;
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
        $providerName = $settings->getIpLookupProvider();
        LoggerHelper::info('Looking up IP info from provider ' . $providerName);

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

    /**
     * Clear all IP lookup cache entries
     * 
     * @return bool Whether the cache was successfully cleared
     */
    public function clearCache(): bool
    {
        LoggerHelper::info('Clearing all IP lookup cache entries');
        return \Craft::$app->cache->delete(self::CACHE_KEY_PREFIX . '-*');
    }

}
