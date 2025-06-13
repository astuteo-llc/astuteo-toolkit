<?php

namespace astuteo\astuteotoolkit\services\iplookup;

use astuteo\astuteotoolkit\helpers\LoggerHelper;
use Craft;
use GuzzleHttp\Client;

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
     * {@inheritdoc}
     */
    abstract public function lookup(string $ip): ?array;

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
        return [
            'ip' => $ip,
            'city' => $this->extractCity($data),
            'state' => $this->extractState($data),
            'country' => $this->extractCountry($data),
            'postal' => $this->extractPostal($data),
            'organization' => $this->extractOrganization($data),
            'raw' => $data, // Include the raw data for debugging or custom processing
        ];
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
