## Admin Helpers

System-wide settings to enhance the admin for our workflow.

### Settings
All settings can be configured in a `astuteo-toolkit.php` file in the config directory.


#### Front-end edit button.
If enabled, when a user who has access to edit the element is logged in, a red edit button is rendered.
```php
<?php
    return [
        "includeFeEdit" => true,
    ];
```

#### Nav Shortcuts
If enabled and when site is in devMode admin users will have shortcuts to the sections and fields edit sections
```php
<?php
    return [
        "devCpNav" => true,
    ];
```

[back to index](../README.md) 
