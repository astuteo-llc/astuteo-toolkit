## imgixTransformMap

### What does this do?
Attempts to map Craft's built-in transform functions with corresponding Imgix's URL based API. It's meant to be used in conjunction with a site-wide image macro or similar. 

### Configuration
In `config/astuteo-toolkit.php` create a key of `imgixUrl` and set to the URL at .imgix.net.

```php
<?php
    return [
        "imgixUrl" => 'https://client-name.imgix.net',
    ];
```


### Simple Example
```html
{% import 'transforms.twig' as transform %}

{{ transform.image( image, { width: 300, height: 200 } ) }}

```
```
{% macro image(image, options) %}
    <!-- native Craft -->
    {{ image.getUrl(options) }}
    
    <!-- imgix version -->
    {{ craft.astuteoToolkit.imgixTransformMap(image, options) }}
{% endmacro %}
```

Note: This will map cropping and Craft's native focal-point to the URL based version.


### Complex Example
Imgix supports more features than Craft's built-in, to pass those options you can include a third value.

```html
{% import 'transforms.twig' as transform %}

{{ transform.image( image, { width: 300, height: 200 }, {colorize: '#ff9933'} ) }}

```


```html
{% macro image(image, options, extra) %}
    <!-- native Craft (ignores extra) -->
    {{ image.getUrl(options) }}
    
    <!-- imgix version -->
    {{ craft.astuteoToolkit.imgixTransformMap(image, options, extra) }}
{% endmacro %}
```




[back to index](../README.md) 
