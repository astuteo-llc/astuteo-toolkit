<?php

namespace astuteo\astuteotoolkit\services\iplookup;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\helpers\LoggerHelper;
use GuzzleHttp\Client;
use Craft;

/**
 * IP lookup provider using ipwhois.app API
 */
class IpWhoisProvider extends AbstractIpLookupProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getApiUrl(string $ip, string $token): string
    {
        return "http://ipwhois.pro/{$ip}?key={$token}";
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        // IPWhois.pro requires an API token
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
        if (isset($data['connection']) && is_array($data['connection'])) {
            return $data['connection']['org'] ?? ($data['connection']['isp'] ?? ($data['org'] ?? ($data['isp'] ?? null)));
        }
        // Fall back to the "free" version
        return $data['org'] ?? ($data['isp'] ?? null);
    }

    protected function extractIsp(array $data): ?string {
        if(isset($data['connection']) && is_array($data['connection'])) {
            return $data['connection']['isp'] ?? null;
        }
        return ($data['isp'] ?? null);
    }
}
