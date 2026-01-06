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

    // Imgix signed URLs token (legacy - use imgixSignedTokens for multiple domains)
    'imgixSignedToken' => '',

    // Volume handles mapped to Imgix signed tokens
    // Use this to set different secure tokens for each imgix domain/source
    // Example: ['volumeHandle1' => 'token1', 'volumeHandle2' => 'token2']
    'imgixSignedTokens' => [],

    // Lazy load attribute prefix
    'lazyLoadPrefix' => '',
];
