## insecureCookie

### What does this do?
Gets a cookie that is set by Javascript. Do not use for sensitive information, and instead use Craft's built-in functions.

### Example
```html
{% set cookie = craft.astuteoToolkit.insecureCookie('cookieName') %}
```

### Parameters

```
{{ craft.astuteoToolkit.insecureCookie(
    <cookie-name>, 
    <default-value>, (optional)
) }}
```

[back to index](../README.md) 
