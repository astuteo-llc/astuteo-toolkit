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
    // IP lookup provider ('ipinfo' or 'ipwhois')
    'ipLookupProvider' => 'ipinfo',
    
    // API token for the IP lookup service (should be kept private)
    'ipLookupToken' => getenv('IP_LOOKUP_TOKEN'),
    
    // Authentication token for IP controller endpoints
    'ipControllerToken' => getenv('IP_CONTROLLER_TOKEN') ?: 'astuteo-toolkit',
    
    // Development IP address for testing in dev mode
    'devIpAddress' => getenv('DEV_IP_ADDRESS') ?: '',
];
```

### Environment Variables

For security, it's recommended to store your API tokens as environment variables:

```
# .env
IP_LOOKUP_TOKEN=your_api_token_here
IP_CONTROLLER_TOKEN=your_controller_token_here
```

## Available Providers

### ipinfo.io

The default provider that offers comprehensive IP data. Requires an API token from [ipinfo.io](https://ipinfo.io/).

### ipwhois.app

An alternative provider that offers similar data. Requires an API token from [ipwhois.app](https://ipwhois.app/).

## Usage

### Basic Usage

```php
// Get information about the current visitor's IP
$ipInfo = AstuteoToolkit::$plugin->ipLookup->lookup(Craft::$app->request->userIP);

// Access the information
$city = $ipInfo['city'];
$state = $ipInfo['state'];
$country = $ipInfo['country'];
$organization = $ipInfo['organization'];
$isIsp = $ipInfo['is_isp'];
```

### Development Mode

In development environments, you can simulate a specific IP address:

```php
// In config/astuteo-toolkit.php
return [
    'devIpAddress' => '184.61.146.48',
];
```

When `devMode` is enabled, all IP lookups will use this IP address instead of the actual visitor IP.

## API Reference

### IpLookupService

#### `lookup(string $ip): ?array`

Looks up information about an IP address.

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

#### `setIpControllerToken(string $token): bool`

Sets the authentication token for IP controller endpoints.

**Parameters:**
- `$token` (string): The controller token

**Returns:**
- `bool`: Whether the token was successfully set

#### `validateControllerToken(string $token): bool`

Validates an authentication token against the configured IP controller token.

**Parameters:**
- `$token` (string): The token to validate

**Returns:**
- `bool`: Whether the token is valid

#### `clearCache(): bool`

Clears all IP lookup cache entries.

## Examples

### Checking if a visitor is from an ISP

```php
$ipInfo = AstuteoToolkit::$plugin->ipLookup->lookup(Craft::$app->request->userIP);

if ($ipInfo && $ipInfo['is_isp']) {
    echo "This visitor is coming from an ISP: " . $ipInfo['organization'];
}
```

### Getting location information in a Twig template

```twig
{% set ipInfo = craft.astuteoToolkit.getIpInfo() %}

{% if ipInfo %}
    <p>Your location: {{ ipInfo.city }}, {{ ipInfo.state }}, {{ ipInfo.country }}</p>
{% endif %}
```

### Setting a custom API token programmatically

```php
$success = AstuteoToolkit::$plugin->ipLookup->setIpLookupToken('your_api_token_here');

if ($success) {
    echo "API token updated successfully";
}
```