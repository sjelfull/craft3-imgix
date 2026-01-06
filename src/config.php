<?php
/**
 * Imgix plugin for Craft CMS 5.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */

/**
 * Imgix config.php
 *
 * This file exists only as a template for the Imgix settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'config' as 'imgix.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // Imgix API key
    'apiKey' => '',

    // Volume handles mapped to Imgix domains
    'imgixDomains' => [],

    // Imgix signed URLs token
    'imgixSignedToken' => '',

    // Lazy load attribute prefix
    'lazyLoadPrefix' => '',

    // Auto-generate transforms on asset upload/save
    // Set to true to enable for all volumes, or provide an array of volume handles
    'autoGenerate' => false,

    // Transform definitions to generate automatically
    // Can be defined globally or per volume handle
    // Example:
    // 'transforms' => [
    //     // Global transforms applied to all volumes with autoGenerate enabled
    //     'global' => [
    //         ['width' => 400, 'height' => 300],
    //         ['width' => 800, 'height' => 600],
    //         ['width' => 1200],
    //     ],
    //     // Volume-specific transforms
    //     'volumeHandle' => [
    //         ['width' => 400, 'height' => 300, 'fit' => 'crop'],
    //         ['width' => 800, 'height' => 600, 'fit' => 'crop'],
    //     ],
    // ]
    'transforms' => [],
];
