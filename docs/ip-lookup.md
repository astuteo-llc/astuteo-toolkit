# IP Lookup Service

The IP Lookup Service provides geolocation and organization information for IP addresses. It can be used to determine a visitor's location, identify if they're coming from an ISP, and more.

## Overview

The IP Lookup Service:
- Retrieves geolocation data (city, state, country, postal code)
- Identifies organization/company information
- Automatically detects and marks ISP connections
- Caches results for improved performance
- Supports multiple lookup providers

## Configuration

### Basic Configuration

Configure the IP lookup service in your `config/astuteo-toolkit.php` file:

```php
return [
    // Authentication token for IP controller endpoints
    'ipControllerToken' => getenv('IP_CONTROLLER_TOKEN') ?: 'random-string-no-plus',
    
    // Development IP address for testing in dev mode
    'devIpAddress' =>   '',
];
```


## Available Providers

### ipinfo.io

The default provider that offers comprehensive IP data. Requires an API token from [ipinfo.io](https://ipinfo.io/).

### ipwhois.app

An alternative provider that offers similar data. Requires an API token from [ipwhois.app](https://ipwhois.app/).

### Development Mode

In development environments, you can simulate a specific IP address:

```php
// In config/astuteo-toolkit.php
return [
    'devIpAddress' => '184.61.146.48',
];
```

When `devMode` is enabled, all IP lookups will use this IP address instead of the actual visitor IP.

### IpLookupService

#### `lookup(string $ip): ?array`

**Parameters:**
- `$ip` (string): The IP address to look up

**Returns:**
An array containing:
- `ip` (string): The IP address
- `city` (string|null): The city name
- `state` (string|null): The state/region
- `country` (string|null): The country
- `postal` (string|null): The postal code
- `organization` (string|null): The organization name
- `is_isp` (bool): Whether the organization is likely an ISP
- `raw` (array): The raw data from the provider

#### `getProvider(): ?IpLookupProviderInterface`

Gets the configured IP lookup provider.

#### `setIpLookupToken(string $token): bool`

Sets the API token for the IP lookup service.

**Parameters:**
- `$token` (string): The API token

**Returns:**
- `bool`: Whether the token was successfully set

