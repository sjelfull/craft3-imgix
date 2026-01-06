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
    'transforms' => [
        // Global transforms applied to all volumes with autoGenerate enabled
        'global' => [
            // Responsive thumbnail sizes
            ['width' => 400, 'height' => 300, 'fit' => 'crop'],
            ['width' => 800, 'height' => 600, 'fit' => 'crop'],
            
            // Hero image sizes
            ['width' => 1200, 'height' => 675, 'fit' => 'crop'],
            ['width' => 1920, 'height' => 1080, 'fit' => 'crop'],
            
            // Portrait orientation
            ['width' => 600, 'height' => 800, 'fit' => 'crop'],
        ],
        
        // Volume-specific transforms (merged with global)
        'images' => [
            // Additional transforms for the 'images' volume
            ['width' => 300, 'height' => 300, 'fit' => 'crop', 'crop' => 'faces'],
            ['width' => 150, 'height' => 150, 'fit' => 'crop', 'crop' => 'faces'],
        ],
        
        'userUploads' => [
            // Specific transforms for user uploads
            ['width' => 500, 'fit' => 'max'],
            ['width' => 1000, 'fit' => 'max'],
        ],
    ],
];
