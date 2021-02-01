## getVideoEmbedInfo

### What does this do?
Returns the YouTube embed URL and Thumbnail. Optionally uploads and stores the thumbnail in a volume. 

### Configuration
The following values can be set in `config/astuteo-toolkit.php`.

```php
<?php
    return [
       "uploadVideoThumbs" => true,
       "cacheVideoEmbeds" => true,
       "uploadVideoThumbsVolumeId" => 1,
    ];
```


### Example
```html
{% set videoEmbed = craft.astuteoToolkit.getVideoEmbedInfo('https://www.youtube.com/watch?v=8Hk9HWJH_OE') %}
```

If the video exists, videoEmbed will contain:
- `staticThumb` (boolean - true if a static URL, false if a Craft image)
- `url` (YouTube embed URL URL
- `thumbnail`: `url` if static, or full asset model if Craft
- `id` (YouTube's ID)

#### Embed Example:
```html
<iframe src="{{ videoEmbed.url }}" frameborder="0"></iframe>
```

#### Simple Thumbnail Example:
```html
<img src="{{ videoEmbed.thumbnail.url }}">
```

#### Complex Thumbnail Example:
```html
{% if info.staticThumb %}
  <img src="{{ videoEmbed.thumbnail.url }}">
{% else %}
  <img src="{{ videoEmbed.thumbnail.getUrl({ width: 300}) }}">
{% endif %}
```

### Storing Thumbnails in Volume

If you want to store thumbnails in a Craft asset volume a few configuration settings to be added to `config/astuteo-toolkit.php`.

1. Create a Volume target for the Assets. It is recommended creating a dedicated volume and to not allow normal access to the volume. Set the `uploadVideoThumbsVolumeId` key to the ID of the volume.
2. Set `uploadVideoThumbs` to true. The uploading happens the first time the videos are rendered. Subsequently the results are either cached (recommended) or it checks for the filename. The filename is the YouTube <video ID>.jpg. If there are multiple thumbnails, this may timeout and it will try again on next access.
3. If there are multiple videos per page, it is recommended to set `cacheVideoEmbeds` to true.
4. TEST locally first and be sure the Volume ID is correct.


[back to index](../README.md) 
