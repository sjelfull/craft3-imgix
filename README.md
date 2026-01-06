<!-- ix-docs-ignore -->
# imgix plugin for Craft CMS 5.x

Use imgix with Craft

![Screenshot](resources/img/plugin-icon.png)

---
<!-- /ix-docs-ignore -->

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Quick Start](#quick-start)
  - [Configuration Options](#configuration-options)
  - [Environment Variables](#environment-variables)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Transform Options](#transform-options)
  - [Lazy Loading](#lazy-loading)
  - [Advanced Usage](#advanced-usage)
- [Common Use Cases](#common-use-cases)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)

## Requirements

This plugin requires Craft CMS 5.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

    ```bash
    cd /path/to/project
    ```

2. Then tell Composer to load the plugin:

    ```bash
    composer require superbig/craft3-imgix
    ```

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for imgix.

## Configuration

### Quick Start

Copy the `config.php` file from the plugin's `src` directory into your Craft project's `config` folder and rename it to `imgix.php`.

**Basic configuration example:**

```php
<?php
return [
    // Map your Craft volume handles to imgix domains
    'imgixDomains' => [
        'myVolumeHandle' => 'my-site.imgix.net',
    ],
];
```

### Configuration Options

Create a `config/imgix.php` file with the following options:

```php
<?php

use craft\helpers\App;

return [
    // imgix API key (required for purging assets)
    // Generate a new API key at: https://dashboard.imgix.com/api-keys/new
    // Ensure it has 'Purge' permissions
    'apiKey' => App::env('IMGIX_API_KEY'),

    // Map Craft volume handles to imgix domains (required)
    // This tells the plugin which imgix domain to use for each asset volume
    'imgixDomains' => [
        // Format: 'volumeHandle' => 'your-domain.imgix.net'
        'uploads' => 'my-project.imgix.net',
        'heroImages' => 'my-project.imgix.net/heroes',
        'products' => 'my-shop.imgix.net',
    ],

    // imgix signed URL token (optional, but recommended for security)
    // Generate from: https://dashboard.imgix.com/sources
    // Prevents URL tampering and unauthorized image transformations
    'imgixSignedToken' => App::env('IMGIX_SIGNED_TOKEN'),

    // Lazy load attribute prefix (optional, defaults to 'data-')
    // Used when lazyLoad is enabled to prefix src attributes
    // Set to empty string if using native loading="lazy"
    'lazyLoadPrefix' => 'data-',
];
```

**Understanding Volume Mapping:**

The `imgixDomains` array maps your Craft volume handles to imgix domains. The plugin looks up the asset's volume handle to determine which imgix domain to use.

- **Simple mapping:** `'uploads' => 'my-site.imgix.net'` - all assets from the 'uploads' volume use this domain
- **Path mapping:** `'heroImages' => 'my-site.imgix.net/heroes'` - adds a path prefix to all images
- **Multiple domains:** You can use different imgix domains for different volumes

### Environment Variables

It's recommended to store sensitive values like API keys in environment variables. In your `.env` file:

```bash
IMGIX_API_KEY="your-api-key-here"
IMGIX_SIGNED_TOKEN="your-signed-token-here"
```

Then reference them in `config/imgix.php`:

```php
<?php

use craft\helpers\App;

return [
    'apiKey' => App::env('IMGIX_API_KEY'),
    'imgixSignedToken' => App::env('IMGIX_SIGNED_TOKEN'),
    'imgixDomains' => [
        'uploads' => App::env('IMGIX_DOMAIN'),
    ],
];
```

## Usage

### Basic Usage

The plugin provides the `craft.imgix.transformImage()` method in Twig templates to transform images using imgix.

**Simple image transformation:**

```twig
{# Transform a single asset #}
{% set asset = entry.featuredImage.one() %}
{% set transformedImage = craft.imgix.transformImage(asset, { width: 400, height: 300 }) %}

{# Output the image tag #}
{{ transformedImage.img() }}

{# Or just get the URL #}
{{ transformedImage.getUrl() }}
```

**Multiple transforms (responsive images):**

```twig
{% set transforms = [
    { width: 400, height: 300 },
    { width: 800, height: 600 },
    { width: 1200, height: 900 },
] %}

{% set transformedImage = craft.imgix.transformImage(asset, transforms) %}

{# Output with srcset for responsive images #}
{{ transformedImage.srcset() }}
```

### Transform Options

imgix supports a wide range of transformation parameters. Here are the most commonly used:

**Size & Cropping:**

```twig
{# Fixed dimensions #}
{% set image = craft.imgix.transformImage(asset, {
    width: 800,
    height: 600,
    fit: 'crop',  # Options: crop, clip, clamp, facearea, fill, fillmax, max, min, scale
}) %}

{# Aspect ratio (maintains ratio, calculates height) #}
{% set image = craft.imgix.transformImage(asset, {
    width: 1920,
    ratio: 16/9
}) %}

{# Maximum dimensions (won't exceed these) #}
{% set image = craft.imgix.transformImage(asset, {
    'max-width': 1200,
    'max-height': 800,
}) %}
```

**Image Quality & Format:**

```twig
{% set image = craft.imgix.transformImage(asset, {
    width: 800,
    auto: 'format,compress',  # Automatically optimize format and compression
    q: 75,  # Quality (1-100)
    fm: 'webp',  # Force format: webp, jpg, png, gif, etc.
}) %}
```

**Effects & Adjustments:**

```twig
{% set image = craft.imgix.transformImage(asset, {
    width: 800,
    sharp: 10,  # Sharpness (0-100)
    blur: 0,  # Blur (0-2000)
    bri: 0,  # Brightness (-100 to 100)
    con: 0,  # Contrast (-100 to 100)
    sat: 0,  # Saturation (-100 to 100)
}) %}
```

**Default Options:**

Apply default options to all transforms:

```twig
{% set transforms = [
    { width: 400 },
    { width: 800 },
    { width: 1200 },
] %}

{% set defaultOptions = {
    sharp: 10,
    auto: 'format,compress',
    q: 80
} %}

{% set image = craft.imgix.transformImage(asset, transforms, defaultOptions) %}
{{ image.srcset() }}
```

**Focal Points:**

The plugin automatically uses Craft's focal point if set on the asset:

```twig
{# Focal point from Craft asset is automatically applied #}
{% set image = craft.imgix.transformImage(asset, {
    width: 800,
    height: 600,
    fit: 'crop'
}) %}
```

### Lazy Loading

**JavaScript-based lazy loading:**

```twig
{# Single image with lazy loading #}
{% set image = craft.imgix.transformImage(asset, { width: 800 }) %}
{{ image.img({ lazyLoad: true }) }}
{# Outputs: <img data-src="..." /> #}

{# Responsive image with lazy loading #}
{% set transforms = [
    { width: 400 },
    { width: 800 },
    { width: 1200 },
] %}
{% set image = craft.imgix.transformImage(asset, transforms) %}
{{ image.srcset({ lazyLoad: true }) }}
{# Outputs: <img data-src="..." data-srcset="..." /> #}
```

**Native browser lazy loading:**

```twig
{% set image = craft.imgix.transformImage(asset, { width: 800 }) %}
{{ image.img({ loading: 'lazy' }) }}
{# Outputs: <img src="..." loading="lazy" /> #}

{# Or with srcset #}
{{ image.srcset({ loading: 'lazy' }) }}
```

**Custom lazy load prefix:**

In your `config/imgix.php`:

```php
return [
    'lazyLoadPrefix' => 'lazy-',  # Will use lazy-src, lazy-srcset
    // ...
];
```

### Advanced Usage

**Using with Element API:**

```php
<?php

use craft\elements\Entry;
use superbig\imgix\Imgix;

return [
    'endpoints' => [
        'news.json' => [
            'elementType' => Entry::class,
            'criteria' => ['section' => 'news'],
            'transformer' => function(Entry $entry) {
                $asset = $entry->featuredImage->one();
                $featuredImage = Imgix::$plugin->imgixService->transformImage($asset, [
                    'width' => 400,
                    'height' => 350
                ]);
                
                return [
                    'title' => $entry->title,
                    'url' => $entry->url,
                    'featuredImage' => [
                        'url' => $featuredImage->getUrl(),
                        'width' => 400,
                        'height' => 350,
                    ],
                ];
            },
        ],
    ]
];
```

**Custom attributes on image tags:**

```twig
{% set image = craft.imgix.transformImage(asset, { width: 800 }) %}

{# Add custom attributes #}
{{ image.img({
    alt: 'Description of image',
    class: 'img-fluid rounded',
    id: 'hero-image',
    'data-gallery': 'main'
}) }}

{# With srcset #}
{{ image.srcset({
    alt: 'Description of image',
    class: 'responsive-img',
    sizes: '(max-width: 600px) 100vw, 50vw'
}) }}
```

**Working with string URLs:**

```twig
{# Pass a URL string instead of an asset #}
{% set image = craft.imgix.transformImage('/path/to/image.jpg', { width: 800 }) %}
{{ image.getUrl() }}
{# Uses the first domain in imgixDomains config #}
```

**Debugging transforms:**

```twig
{% set image = craft.imgix.transformImage(asset, transforms) %}

{# See all transformed image data #}
{{ dump(image.transformed) }}
```

## Common Use Cases

### Responsive Hero Image

```twig
{% set asset = entry.heroImage.one() %}
{% set transforms = [
    { width: 640, height: 400, fit: 'crop' },
    { width: 1024, height: 640, fit: 'crop' },
    { width: 1920, height: 1200, fit: 'crop' },
] %}

{% set defaultOptions = {
    auto: 'format,compress',
    q: 85,
    sharp: 5
} %}

{% set heroImage = craft.imgix.transformImage(asset, transforms, defaultOptions) %}

{{ heroImage.srcset({
    alt: entry.title,
    class: 'hero-image',
    sizes: '100vw',
    loading: 'eager'
}) }}
```

### Product Thumbnail Gallery

```twig
{% set productImages = entry.productGallery.all() %}

<div class="product-gallery">
    {% for asset in productImages %}
        {% set thumb = craft.imgix.transformImage(asset, {
            width: 300,
            height: 300,
            fit: 'crop',
            auto: 'format,compress'
        }) %}
        
        <a href="{{ asset.url }}" data-lightbox="gallery">
            {{ thumb.img({
                alt: asset.title,
                class: 'thumbnail',
                loading: 'lazy'
            }) }}
        </a>
    {% endfor %}
</div>
```

### Blog Post Featured Images

```twig
{% for entry in craft.entries.section('blog').all() %}
    {% set featuredImage = entry.featuredImage.one() %}
    
    {% if featuredImage %}
        {% set image = craft.imgix.transformImage(featuredImage, {
            width: 800,
            height: 450,
            fit: 'crop',
            auto: 'format',
            q: 75
        }) %}
        
        <article>
            <a href="{{ entry.url }}">
                {{ image.img({
                    alt: entry.title,
                    loading: 'lazy'
                }) }}
            </a>
            <h2>{{ entry.title }}</h2>
        </article>
    {% endif %}
{% endfor %}
```

### Art Direction with Different Crops

```twig
{% set asset = entry.bannerImage.one() %}

{# Mobile: Square crop #}
{% set mobile = craft.imgix.transformImage(asset, {
    width: 640,
    height: 640,
    fit: 'crop',
    auto: 'format'
}) %}

{# Desktop: Wide crop #}
{% set desktop = craft.imgix.transformImage(asset, {
    width: 1920,
    height: 600,
    fit: 'crop',
    auto: 'format'
}) %}

<picture>
    <source media="(min-width: 768px)" srcset="{{ desktop.getUrl() }}">
    <img src="{{ mobile.getUrl() }}" alt="{{ entry.title }}">
</picture>
```

### Background Image with Blur Effect

```twig
{% set asset = entry.backgroundImage.one() %}
{% set bgImage = craft.imgix.transformImage(asset, {
    width: 1920,
    height: 1080,
    fit: 'crop',
    blur: 50,
    auto: 'format'
}) %}

<div class="hero" style="background-image: url('{{ bgImage.getUrl() }}');">
    {# Content here #}
</div>
```

### Optimized Avatar Images

```twig
{% set avatar = currentUser.photo.one() %}

{% if avatar %}
    {% set avatarImage = craft.imgix.transformImage(avatar, {
        width: 80,
        height: 80,
        fit: 'crop',
        'border-radius': '50%',  {# imgix can create circular images #}
        auto: 'format,compress'
    }) %}
    
    {{ avatarImage.img({ alt: currentUser.fullName, class: 'avatar' }) }}
{% endif %}
```

## Troubleshooting

### Images Not Loading

**Problem:** Images aren't transforming or showing up.

**Solutions:**
1. Check that your volume handle matches the key in `imgixDomains` config
2. Verify your imgix domain is correct in the config file
3. Ensure your imgix source is properly configured to point to your asset storage
4. Check that the asset exists and has a valid path

### Signed URLs Not Working

**Problem:** Getting 403 errors or signature mismatches.

**Solutions:**
1. Verify your `imgixSignedToken` matches the token in your imgix source settings
2. Make sure URL signing is enabled in your imgix source
3. Check for trailing/leading whitespace in your token

### Purging Not Working

**Problem:** Asset cache isn't being purged when assets are updated.

**Solutions:**
1. Verify you have a valid API key with purge permissions
2. Generate a new API key from https://dashboard.imgix.com/api-keys/new
3. Ensure the API key has "Purge" permission enabled
4. Check that you're not using an old API key (< 50 characters) - these are deprecated

### Focal Point Not Applied

**Problem:** Crop isn't respecting Craft's focal point.

**Solutions:**
1. Ensure you're using `fit: 'crop'` in your transform
2. Verify the focal point is set on the asset in Craft
3. Check that you're not manually overriding `fp-x` and `fp-y` in your transforms

### Quality Issues

**Problem:** Images look too compressed or low quality.

**Solutions:**
1. Adjust the `q` parameter (quality): `q: 85` for higher quality
2. Use `auto: 'format,compress'` to let imgix optimize automatically
3. Remove excessive sharpening: `sharp: 5-10` is usually sufficient
4. Ensure source images are high quality

### Lazy Loading Not Working

**Problem:** Lazy loading attributes not appearing.

**Solutions:**
1. Verify you're passing `lazyLoad: true` in the options: `{{ image.img({ lazyLoad: true }) }}`
2. Check your `lazyLoadPrefix` setting in config
3. Ensure your JavaScript lazy loading library is properly initialized
4. For native loading, use `loading: 'lazy'` instead of `lazyLoad: true`

### Different Environments

**Problem:** Images work locally but not in production (or vice versa).

**Solutions:**
1. Use environment variables for your imgix configuration
2. Ensure `.env` files are properly configured for each environment
3. Check that volume handles are consistent across environments
4. Verify imgix sources are configured for both development and production URLs

## Roadmap

* Look into improving srcset/API
* Look into built-in image editor integration
* Additional imgix parameter helpers
* Improved focal point handling

---

## Additional Resources

- [imgix Documentation](https://docs.imgix.com/)
- [imgix URL API Reference](https://docs.imgix.com/apis/rendering)
- [Craft CMS Asset Documentation](https://craftcms.com/docs/5.x/reference/element-types/assets.html)

---

Brought to you by [Superbig](https://superbig.co)
