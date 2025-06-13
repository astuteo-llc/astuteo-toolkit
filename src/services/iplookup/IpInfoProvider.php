<?php

namespace astuteo\astuteotoolkit\services\iplookup;

use astuteo\astuteotoolkit\AstuteoToolkit;

/**
 * IP lookup provider using ipinfo.io API
 */
class IpInfoProvider extends AbstractIpLookupProvider
{
    /**
     * {@inheritdoc}
     */
    public function lookup(string $ip): ?array
    {
        if (!$this->isConfigured()) {
            $this->logError('IPInfo token not configured');
            return null;
        }

        $token = AstuteoToolkit::$plugin->getSettings()->ipinfoToken;
        $url = "https://api.ipinfo.io/lite/{$ip}?token={$token}";

        try {
            $response = $this->client->get($url);
            $data = json_decode((string)$response->getBody(), true);
            
            if (!$data) {
                $this->logError('Failed to decode response from ipinfo.io');
                return null;
            }
            
            return $this->standardizeResponse($data, $ip);
        } catch (\Throwable $e) {
            $this->logError("IPInfo lookup failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        $token = AstuteoToolkit::$plugin->getSettings()->ipinfoToken;
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
    protected function extractOrganization(array $data): ?string
    {
        return $data['org'] ?? ($data['as_name'] ?? null);
    }
}