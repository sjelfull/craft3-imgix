<?php
/**
 * Example Imgix configuration with auto-generate transforms
 *
 * This file demonstrates how to configure the imgix plugin to automatically
 * generate transforms when assets are uploaded or updated.
 *
 * Copy this to your config folder as 'imgix.php' and customize as needed.
 */

return [
    // Imgix API key (required for purging)
    // Get yours from https://dashboard.imgix.com/api-keys
    'apiKey' => getenv('IMGIX_API_KEY') ?: '',

    // Map your Craft volume handles to imgix domains
    // You can specify just the domain, or include a path prefix after the domain
    // Examples:
    //   'volumeHandle' => 'mysite.imgix.net'              - All assets served from root
    //   'volumeHandle' => 'mysite.imgix.net/subfolder'   - All assets prefixed with /subfolder
    'imgixDomains' => [
        'images' => 'mysite.imgix.net',
        'userUploads' => 'mysite.imgix.net/uploads',
    ],

    // Imgix signed URLs token (optional)
    // Enable if you want to secure your imgix URLs
    'imgixSignedToken' => getenv('IMGIX_SIGNED_TOKEN') ?: '',

    // Lazy load attribute prefix (optional)
    // Use 'data-' for lazy loading libraries like lazysizes
    'lazyLoadPrefix' => '',

    // === Named Transforms ===
    
    // Define reusable named transforms (similar to Imager-X)
    // These can be referenced by name in the transforms configuration
    'namedTransforms' => [
        // Thumbnails
        'thumbnail' => ['width' => 200, 'height' => 200, 'fit' => 'crop'],
        'thumbnail_large' => ['width' => 400, 'height' => 400, 'fit' => 'crop'],
        
        // Hero images
        'hero' => ['width' => 1920, 'height' => 1080, 'fit' => 'crop'],
        'hero_mobile' => ['width' => 768, 'height' => 1024, 'fit' => 'crop'],
        
        // Content images
        'content_small' => ['width' => 400, 'fit' => 'max'],
        'content_medium' => ['width' => 800, 'fit' => 'max'],
        'content_large' => ['width' => 1200, 'fit' => 'max'],
        
        // Portrait
        'portrait' => ['width' => 600, 'height' => 800, 'fit' => 'crop'],
        
        // Profile pictures
        'profile' => ['width' => 150, 'height' => 150, 'fit' => 'crop', 'crop' => 'faces'],
        'profile_large' => ['width' => 300, 'height' => 300, 'fit' => 'crop', 'crop' => 'faces'],
    ],

    // === Auto-Generate Transforms Configuration ===

    // Enable auto-generate for specific volumes
    // Options:
    //   false - Disabled (default)
    //   true - Enable for all volumes
    //   ['volume1', 'volume2'] - Enable only for specific volumes
    'autoGenerate' => ['images', 'userUploads'],

    // Warm imgix cache by making HTTP requests (optional)
    // When true, makes HEAD requests to imgix URLs after generation
    // Recommended: false (let imgix process on first user request)
    'warmCache' => false,

    // Transform definitions to generate automatically
    // Supports both full transform definitions and named transform references
    'transforms' => [
        // Global transforms applied to all volumes with autoGenerate enabled
        'global' => [
            // Using named transforms (quick syntax)
            'thumbnail',
            'thumbnail_large',
            'content_small',
            'content_medium',
            'content_large',
            
            // Or define transforms inline
            ['width' => 1200, 'height' => 675, 'fit' => 'crop'],
            ['width' => 1920, 'height' => 1080, 'fit' => 'crop'],
        ],
        
        // Volume-specific transforms (merged with global)
        'images' => [
            // Additional named transforms for the 'images' volume
            'hero',
            'hero_mobile',
            'portrait',
            
            // Additional inline transforms
            ['width' => 300, 'height' => 300, 'fit' => 'crop', 'crop' => 'faces'],
        ],
        
        'userUploads' => [
            // Specific transforms for user uploads
            'profile',
            'profile_large',
            ['width' => 500, 'fit' => 'max'],
            ['width' => 1000, 'fit' => 'max'],
        ],
    ],

    // Prevent upscaling images beyond their original size
    'preventUpscaling' => false,
];
