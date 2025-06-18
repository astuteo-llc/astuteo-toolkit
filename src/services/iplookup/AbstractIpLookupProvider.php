<?php

namespace astuteo\astuteotoolkit\services\iplookup;

use astuteo\astuteotoolkit\helpers\LoggerHelper;
use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use astuteo\astuteotoolkit\AstuteoToolkit;

/**
 * Abstract base class for IP lookup providers
 */
abstract class AbstractIpLookupProvider implements IpLookupProviderInterface
{
    /**
     * @var Client|null Guzzle HTTP client
     */
    protected ?Client $client = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->client = Craft::createGuzzleClient();
    }

    /**
     * Get the API URL for the IP lookup
     * 
     * @param string $ip The IP address to look up
     * @param string $token The API token
     * @return string The complete API URL
     */
    abstract protected function getApiUrl(string $ip, string $token): string;

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ip): ?array
    {
        if (!$this->isConfigured()) {
            $this->logError('Provider not configured');
            return null;
        }

        $token = AstuteoToolkit::$plugin->getSettings()->getIpLookupToken();
        $url = $this->getApiUrl($ip, $token);

        try {
            $response = $this->client->get($url);
            $data = json_decode((string)$response->getBody(), true);

            if (!$data) {
                $this->logError('Failed to decode response from API');
                return null;
            }

            // Check if the request was successful (if the API provides this information)
            if (isset($data['success']) && $data['success'] === false) {
                $this->logError('API lookup failed: ' . ($data['message'] ?? 'Unknown error'));
                return null;
            }

            return $this->standardizeResponse($data, $ip);
        } catch (\Throwable $e) {
            $this->logError("API lookup failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract public function isConfigured(): bool;

    /**
     * Standardize the response format to ensure all providers return the same data structure
     * 
     * @param array $data The raw data from the provider
     * @param string $ip The IP address that was looked up
     * @return array The standardized data
     */
    protected function standardizeResponse(array $data, string $ip): array
    {
        $response = [
            'ip' => $ip,
            'city' => $this->extractCity($data),
            'state' => $this->extractState($data),
            'country' => $this->extractCountry($data),
            'postal' => $this->extractPostal($data),
            'organization' => $this->extractOrganization($data),
        ];

        // Only include raw data in dev mode for debugging purposes
        if (App::devMode()) {
            $response['raw'] = $data;
        }

        return $response;
    }

    /**
     * Extract the city from the provider's response
     * 
     * @param array $data The raw data from the provider
     * @return string|null The city name or null if not available
     */
    abstract protected function extractCity(array $data): ?string;

    /**
     * Extract the state/region from the provider's response
     * 
     * @param array $data The raw data from the provider
     * @return string|null The state/region name or null if not available
     */
    abstract protected function extractState(array $data): ?string;

    /**
     * Extract the country from the provider's response
     * 
     * @param array $data The raw data from the provider
     * @return string|null The country name or null if not available
     */
    abstract protected function extractCountry(array $data): ?string;

    /**
     * Extract the postal code from the provider's response
     * 
     * @param array $data The raw data from the provider
     * @return string|null The postal code or null if not available
     */
    abstract protected function extractPostal(array $data): ?string;

    /**
     * Extract the organization/company from the provider's response
     * 
     * @param array $data The raw data from the provider
     * @return string|null The organization/company name or null if not available
     */
    abstract protected function extractOrganization(array $data): ?string;

    /**
     * Log an error message
     * 
     * @param string $message The error message
     * @return void
     */
    protected function logError(string $message): void
    {
        LoggerHelper::error("IP lookup error: {$message}");
    }
}
