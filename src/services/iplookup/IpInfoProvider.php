<?php

namespace astuteo\astuteotoolkit\services\iplookup;

use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;

/**
 * IP lookup provider using ipinfo.io API
 * 
 * This provider uses the ipinfo.io API to look up information about IP addresses.
 * It requires an API token to be configured in the plugin settings.
 * 
 * @see https://ipinfo.io/
 */
class IpInfoProvider extends AbstractIpLookupProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getApiUrl(string $ip, string $token): string
    {
        return "https://api.ipinfo.io/lite/{$ip}?token={$token}";
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        $token = AstuteoToolkit::$plugin->getSettings()->getIpLookupToken();
        return !empty($token);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractCity(array $data): ?string
    {
        return $data['city'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractState(array $data): ?string
    {
        return $data['region'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractCountry(array $data): ?string
    {
        return $data['country'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractPostal(array $data): ?string
    {
        return $data['postal'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractOrganization(array $data): ?string
    {
        return $data['org'] ?? ($data['as_name'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractIsp(array $data): ?string
    {
        // First try to extract from the paid tier format (asn.name)
        if (is_array($data['asn'] ?? null) && isset($data['asn']['name'])) {
            return $data['asn']['name'];
        }

        // Fall back to the free tier format (as_name)
        return $data['as_name'] ?? null;
    }


}
