<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\variables;

use superbig\imgix\Imgix;

use Craft;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null  $asset
     * @param null  $transforms
     * @param array $defaultOptions
     *
     * @return string
     *
     */
    public function transformImage ($asset = null, $transforms = null, $defaultOptions = [])
    {
        return Imgix::$plugin->imgixService->transformImage($asset, $transforms, $defaultOptions);
    }
}
