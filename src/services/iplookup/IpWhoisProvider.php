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
    public function __construct()
    {
        $this->client = Craft::createGuzzleClient();
    }

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ip): ?array
    {
        $token = AstuteoToolkit::$plugin->getSettings()->getIpLookupToken();
        $url = "http://ipwhois.pro/{$ip}?key={$token}";

        try {
            $response = $this->client->get($url);
            $data = json_decode((string)$response->getBody(), true);

            if (!$data) {
                $this->logError('Failed to decode response from ipwhois.pro');
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
}
