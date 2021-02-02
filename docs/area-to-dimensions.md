## areaToDimensions

### What does this do?
Accepts an image and target area and returns a width and height to resize the image to. Useful when trying to equalize visual weight of image resizing in areas such as a logo bar. 

### Example

```html
{% set image = craft.asset.id(1).one() %}
{% set targetResize = craft.astuteoToolkit.areaToDimensions(image, 30000) %}
{% set resizedImage = image.getUrl(targetResize) %}
```

### Parameters

```
{{ craft.astuteoToolkit.areaToDimensions(
    <craft image model>, 
    <target area int>,
    <max width int>, (optional)
    <max height int> (optional)
) }}

returns 
{
 <width int>,
 <height int>
}
```

[back to index](../README.md) 
