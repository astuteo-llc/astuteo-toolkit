# ImgixCompatibilityHelper

## What does this do?
Maps Imgix parameters to Imager-X for seamless transition between services. This helper provides compatibility between Imgix and Imager-X, allowing you to use Imgix-style parameters with the Imager-X plugin.

## Requirements
- Craft CMS 3.x, 4.x, or 5.x
- PHP 8.0 or newer
- Imager-X plugin (optional, falls back to Craft's native transforms if not available)

## Usage
The ImgixCompatibilityHelper is exposed through the `transformImagerX` method in the AstuteoToolkit variable:

```twig
{% set transformedImage = craft.astuteoToolkit.transformImagerX(
    image,
    { w: 300, h: 200, fit: 'crop' },
    { auto: 'format,compress', q: 90 }
) %}

<img src="{{ transformedImage }}" alt="{{ image.title }}">
```

### Parameters
1. `image`: The image asset to transform
2. `options`: Main transform options (width, height, fit, etc.)
3. `serviceOptions`: Additional service-specific options (format, quality, effects, etc.)

### Auto-select Transform Service
You can also use the `auto` method to automatically select the best available transform service:

```twig
{% set transformedImage = craft.astuteoToolkit.auto(
    image,
    { w: 300, h: 200, fit: 'crop' },
    { auto: 'format,compress', q: 90 }
) %}
```

This will use Imager-X if available, or fall back to Craft's native transforms.

## Compatibility
This helper is compatible with Craft 3, 4, and 5, and works with PHP 8.0 or newer.

[back to index](../README.md)