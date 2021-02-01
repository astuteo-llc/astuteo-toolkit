## projectVars

### What does this do?
A simple way to include a consistent array of static (or PHP generated) values anywhere in our templates. If requirements are complex, this should be moved to a project-specific module or plugin instead.


### Creating values
By default, value returns an empty array and requires it to be set in the project's config directory. In `config/astuteo-toolkit.php` create a key of `projectVars`. 

Example below:

```php
<?php
    return [
        "projectVars" => [
            'cartAction' => [
                'checkout' => '/cart/checkout',
                'cart' => '/cart',
                'store' => '/products/store',
            ],
            'cartName' => 'Cart',
        ]
    ];
```

From the Twig templates it can be accessed {{ craft.astuteoToolkit.projectVars.cartName }}

Another use case could be with a design that has various components that shared a colorscheme, you can set the color scheme keys in projectVars and access it anywhere in the templates.

Example:

```php
<?php
    return [
        "projectVars" => [
            "darkGray" => [
                "eyebrow" => 'text-gray-300',
                "heading" => 'text-gray-100',
                "subhead" => 'text-gray-100',
                "copy" => 'text-gray-400',
                "button" => '',
                "background" => 'bg-gray-800',
                "gradient" => 'bg-gradient-to-b to-gray-800 from-gray-900 bg-gray-800',
            ],
        ]
    ];
```

And from the Twig template:

```html
    {% set colorScheme = craft.astuteoToolkit.projectVars.darkGray %}
    <div class="{{ colorScheme.background }}">
        <h3 class="{{ colorScheme.heading }} text-3xl my-2">Heading Here</h3>
        <p class="{{ colorScheme.copy }} text-base">Copy Here</p>
    </div>
    
```

[back to index](../README.md) 
