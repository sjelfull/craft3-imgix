<!-- ix-docs-ignore -->
# imgix plugin for Craft CMS 5.x

Use imgix with Craft

![Screenshot](resources/img/plugin-icon.png)

---
<!-- /ix-docs-ignore -->

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuring imgix](#configuring-imgix)
- [Using imgix](#using-imgix)
- [Lazy Loading](#lazy-loading)
- [Roadmap](#roadmap)

## Requirements

This plugin requires Craft CMS 5.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

    ```
    cd /path/to/project
    ```

2. Then tell Composer to load the plugin:

    ```
    composer require superbig/craft3-imgix
    ```

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for imgix.

## Configuring imgix

Copy `config.php` into Craft's `config` folder and rename it to `imgix.php`.

Then map your Asset Source handle to your imgix domain, according to the example.

This plugin will lookup the Asset image's source handle, and figure out which imgix domain to use. If a URL string is passed, it will use the first domain in the config file.

### Basic Configuration (String Format)

```php
<?php
   return [
       // imgix API key
       'apiKey'         => '',

       // Volume handles mapped to imgix domains
       'imgixDomains'   => [
           'volumeHandle1' => 'domain1.imgix.net',
           'volumeHandle2' => 'domain2.imgix.net/subfolder',
       ],

       // Lazy load attribute prefix
       'lazyLoadPrefix' => '',
   ];
```

### Advanced Configuration (Array Format)

For more control, you can use the array format to specify domain, signing token, and path prefix per volume:

```php
<?php
   return [
       'apiKey' => '',
       
       'imgixDomains' => [
           'uploads' => [
               'domain' => 'uploads.imgix.net',
               'signingToken' => '$IMGIX_UPLOADS_TOKEN',  // Optional
               'path' => 'subfolder/path',                 // Optional
           ],
           'products' => [
               'domain' => 'products.imgix.net',
               'signingToken' => '$IMGIX_PRODUCTS_TOKEN',
           ],
           // You can mix string and array formats
           'publicImages' => 'public.imgix.net',
       ],
       
       'lazyLoadPrefix' => '',
   ];
```

### Secure URLs

Each imgix source can have its own secure token for signed URLs. Use the `signingToken` key in the array format:

```php
'imgixDomains' => [
    'uploads' => [
        'domain' => 'my-uploads.imgix.net',
        'signingToken' => 'secure_token_for_uploads_source',
    ],
    'products' => [
        'domain' => 'my-products.imgix.net',
        'signingToken' => 'secure_token_for_products_source',
    ],
],
```

**Note:** The `imgixSignedToken` configuration option is deprecated. Use `signingToken` in the `imgixDomains` array instead.

## Using imgix

```twig
{% set transforms = [
    {
        width: 400,
        height: 300
    },
    {
        width: 940,
        height: 520
    },
    {
        width: 1400,
    },
] %}

{% set defaultOptions = {
    sharp: 10
} %}

{% set firstImage = craft.imgix.transformImage( asset, { width: 400, height: 350 }) %}
{% set secondImage = craft.imgix.transformImage( asset, transforms) %}
{% set thirdImage = craft.imgix.transformImage( asset, { width: 1920, ratio: 16/9}) %}
{% set fourthImage = craft.imgix.transformImage( asset, transforms, defaultOptions) }

{# Image tag #}
{{ firstImage.img() }}

{# Get url for the first image #}
{{ firstImage.getUrl() }}

{# Image tag w/ srcset + tag attributes #}
{{ secondImage.srcset({ width: 700, alt: 'your alt text here' }) }}

{# Image tag w/ srcset + default options for each transform #}
{{ fourthImage.srcset( {} ) }}

{# Image tag w/ lazyload #}
{{ firstImage.img({ lazyLoad: true }) }}

{# Image tag w/ srcset + lazyLoad #}
{{ secondImage.srcset({ lazyLoad: true }) }}

{# See transformed results #}
{{ dump(secondImage.transformed) }}
```

To use with Element API, you can call the service directly:

```php
<?php

use craft\elements\Entry;
use craft\helpers\UrlHelper;
use superbig\imgix\Imgix;

return [
    'endpoints' => [
        'news.json' => [
            'elementType' => Entry::class,
            'criteria' => ['section' => 'news'],
            'transformer' => function(Entry $entry) {
                $asset = $entry->featuredImage->one();
                $featuredImage = Imgix::$plugin->imgixService->transformImage( $asset, [ 'width' => 400, 'height' => 350 ]);
                
                return [
                    'title' => $entry->title,
                    'url' => $entry->url,
                    'jsonUrl' => UrlHelper::url("news/{$entry->id}.json"),
                    'summary' => $entry->summary,
                    'featuredImage' => $featuredImage,
                ];
            },
        ],
    ]
];
```

## Lazy Loading

To replace `src` and `srcset` with `data-src` and `data-srcset` for javascript-based lazy loading, add the `lazyLoad` attribute to `transformImage()`.

If you need to prefix with something other than `data-`, you can set the configuration value `lazyLoadPrefix` in `config/imgix.php`.

Alternatively, you may use the native loading attribute `loading="lazy"` on your image tag as in this example: `{{ image.srcset({ loading: 'lazy' }) }}`.

## Roadmap

* Look into improving srcset/API
* Look into built-in image editor and focal points 
* Improve docs

Brought to you by [Superbig](https://superbig.co)
