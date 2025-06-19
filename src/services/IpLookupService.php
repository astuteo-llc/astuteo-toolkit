<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\helpers\LoggerHelper;
use astuteo\astuteotoolkit\models\IspDetector;
use astuteo\astuteotoolkit\services\iplookup\IpInfoProvider;
use astuteo\astuteotoolkit\services\iplookup\IpLookupProviderInterface;
use astuteo\astuteotoolkit\services\iplookup\IpWhoisProvider;
use craft\helpers\App;
use yii\base\Component;

/**
 * IP Lookup Service for retrieving geolocation and organization information
 * 
 * @property-read IpLookupProviderInterface|null $provider
 */
class IpLookupService extends Component
{
    /**
     * Cache key prefix for all IP lookup cache entries
     */
    public const CACHE_KEY_PREFIX = 'astuteo-toolkit-ip-lookup';

    /**
     * The current provider instance
     */
    private ?IpLookupProviderInterface $provider = null;

    /**
     * The ISP detector instance
     */
    private ?IspDetector $ispDetector = null;

    /**
     * Look up information about an IP address
     * 
     * @param string $ip The IP address to look up
     * @return array{
     *     ip: string,
     *     city: string|null,
     *     state: string|null,
     *     country: string|null,
     *     postal: string|null,
     *     organization: string|null,
     *     isp: string|null,
     *     is_isp: bool,
     *     raw?: array
     * }|null An array containing standardized IP information or null on failure
     * Note: The 'raw' field is only included when in dev mode
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
            // Process organization name in cached result to identify ISPs
            if (isset($cachedResult['organization'])) {
                // If organization name and ISP are the same, it's likely an ISP
                if (isset($cachedResult['isp']) && $cachedResult['organization'] === $cachedResult['isp']) {
                    $cachedResult['is_isp'] = true;
                } else {
                    $ispDetection = $this->getIspDetector()->getIspDetection($cachedResult['organization']);
                    $cachedResult['organization'] = $ispDetection['organization'];
                    $cachedResult['is_isp'] = $ispDetection['is_isp'];
                }
            }

            LoggerHelper::info(sprintf(
                'Retrieved cached IP info for %s (Organization: %s)',
                $ip,
                $cachedResult['organization'] ?? 'Unknown'
            ));
            return $cachedResult;
        }

        $provider = $this->getProvider();
        if (!$provider) {
            LoggerHelper::error('No IP lookup provider available');
            return null;
        }

        $result = $provider->lookup($ip);

        // Process organization name to identify ISPs
        if ($result !== null && isset($result['organization'])) {
            // If organization name and ISP are the same, it's likely an ISP
            if (isset($result['isp']) && $result['organization'] === $result['isp']) {
                $result['is_isp'] = true;
            } else {
                $ispDetection = $this->getIspDetector()->getIspDetection($result['organization']);
                $result['organization'] = $ispDetection['organization'];
                $result['is_isp'] = $ispDetection['is_isp'];
            }
        }

        // Cache the result for 60 days
        if ($result !== null) {
            \Craft::$app->cache->set($cacheKey, $result, 60 * 24 * 60 * 60); // 60 days in seconds
            LoggerHelper::info(sprintf(
                'Performed new IP lookup for %s (Organization: %s)',
                $ip,
                $result['organization'] ?? 'Unknown'
            ));
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
        $this->provider = match ($providerName) {
            'ipinfo' => new IpInfoProvider(),
            'ipwhois' => new IpWhoisProvider(),
            default => null
        };

        if ($this->provider === null) {
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

    /**
     * Get the ISP detector instance
     * 
     * @return IspDetector The ISP detector instance
     */
    private function getIspDetector(): IspDetector
    {
        if ($this->ispDetector === null) {
            $this->ispDetector = new IspDetector();
        }

        return $this->ispDetector;
    }
}
