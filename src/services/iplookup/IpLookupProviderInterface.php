<?php

namespace astuteo\astuteotoolkit\services\iplookup;

/**
 * Interface for IP lookup providers
 */
interface IpLookupProviderInterface
{
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
     *     raw: array
     * }|null An array containing standardized IP information or null on failure
     */
    public function lookup(string $ip): ?array;
    
    /**
     * Check if the provider is configured and ready to use
     *
     * @return bool True if the provider is configured, false otherwise
     */
    public function isConfigured(): bool;
}