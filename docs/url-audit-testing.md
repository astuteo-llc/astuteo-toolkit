# URL Testing Helper

## Overview

Fetch URLs from various sections and category groups within a Craft CMS installation. It's intended for logged-in admin users and can be accessed via a specific URL.

## Endpoint

Admin users can test the functionality by accessing the following endpoint:

`
/actions/astuteo-toolkit/test-url?limit=<LIMIT>&type=<TYPE>
`

- `LIMIT`: The number of URLs to retrieve per section or category group.
- `TYPE`: The type of URLs to return, either `'url'` (absolute URL) or `'uri'` (relative path).

### Example:

`
/actions/astuteo-toolkit/test-url?limit=5
`

#### Parameters:

- `$type`: `'url'` or `'uri'` (default `'url'`).
- `$limit`: The maximum number of URLs to fetch per section or category group (default `1`).
- `$mode`: Optional. A string that specifies the mode of operation. If set to 'css-testing', the function will return URIs formatted specifically for CSS testing. Default is null.

#### Return:

An array of URLs.

### Customization

You can modify the `$limit` and `$type` via query parameters when calling the endpoint. These get passed down to the `getAllUrls` method.

## Example Usage

1. Ensure you are logged in as an admin.
2. Navigate to `/actions/astuteo-toolkit/test-url?limit=5`.
3. The system will return up to 5 URLs from each section and category group, in JSON format.
