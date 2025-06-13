<?php

namespace astuteo\astuteotoolkit\services\iplookup;

use astuteo\astuteotoolkit\AstuteoToolkit;

/**
 * IP lookup provider using ipwhois.app API
 */
class IpWhoisProvider extends AbstractIpLookupProvider
{
    /**
     * {@inheritdoc}
     */
    public function lookup(string $ip): ?array
    {
        $url = "https://ipwhois.app/json/{$ip}";
        
        try {
            $response = $this->client->get($url);
            $data = json_decode((string)$response->getBody(), true);
            
            if (!$data) {
                $this->logError('Failed to decode response from ipwhois.app');
                return null;
            }
            
            // Check if the request was successful
            if (isset($data['success']) && $data['success'] === false) {
                $this->logError('IPWhois lookup failed: ' . ($data['message'] ?? 'Unknown error'));
                return null;
            }
            
            return $this->standardizeResponse($data, $ip);
        } catch (\Throwable $e) {
            $this->logError("IPWhois lookup failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        // IPWhois.app doesn't require authentication for basic usage
        return true;
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
        return $data['org'] ?? ($data['isp'] ?? null);
    }
}