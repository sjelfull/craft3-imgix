# Imgix plugin for Craft CMS 3.x

Use Imgix with Craft

![Screenshot](resources/img/plugin-icon.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require superbig/craft3-imgix

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Imgix.

## Configuring Imgix

Copy `config.php` into Crafts `config` folder and rename it to `imgix.php`.

Then map your Asset Source handle to your Imgix domain, according to the example.

This plugin will lookup the Asset image's source handle, and figure out which Imgix domain to use. If a URL string is passed, it will use the first domain in the config file.

```php
<?php
   return [
       // Imgix API key
       'apiKey'         => '',

       // Volume handles mapped to Imgix domains
       'imgixDomains'   => [],

       // Imgix signed URLs token
       'imgixSignedToken' => '',

       // Lazy load attribute prefix
       'lazyLoadPrefix' => '',
   ];
```

## Using Imgix

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
{{ secondImage.srcset({ width: 700 }) }}

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
## Lazy loading

To replace `src` and `srcset` with `data-src` and `data-srcset` for lazy loading, add the `lazyLoad` attribute to to `transformImage()`.

If you need to prefix with something other than `data-`, you can set the configuration value `lazyLoadPrefix` in `craft/config/imgix.php`.

## Imgix Roadmap

* Look into improving srcset/API
* Look into built-in image editor and focal points 
* Improve docs

Brought to you by [Superbig](https://superbig.co)
