## astuteoRev and astuteoMix

Both Twig filters have the same configuration options. Use astuteoRev for older Blendid projects with the string appended during the build process as part of the file.

### Setting the default assets path
By default, our projects expect the production assets to be build in the <web_pub>/site-assets/ directory. To override that, create an `astuteo-toolkit.php` file in the config directory and set the "assetPath" key.

```php
<?php
    return [
        "assetPath" => "/custom-directory/",
    ];
```

### Applying manifest to file
To apply the string from the manifest file, you can use either format:

```{{ astuteoMix('/site-assets/css/app.css') }}```

or 

```{{ '/site-assets/css/app.css'|astuteoMix }}```

### Custom asset path for file
On occasion, such as a multi-site project, you may want to set the path _just_ for that file. You can do that by passing an array. The first string value should be the asset path and the second value the directory, relative to the webroot.

```{{ ['/custom-assets-directory-base/css/app.css', '/custom-assets-directory-base/']|astuteoRev }}```


### Notes
- By default, we are caching the result site-wide. Currently, that is not configurable. 


[back to docs](../README.MD) 
