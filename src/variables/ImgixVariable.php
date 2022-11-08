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

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixVariable
{
    /**
     * @param null  $asset
     * @param null  $transforms
     *
     *
     * @param mixed[] $defaultOptions
     */
    public function transformImage($asset = null, $transforms = null, array $defaultOptions = []): ?\superbig\imgix\models\ImgixModel
    {
        return Imgix::$plugin->imgixService->transformImage($asset, $transforms, $defaultOptions);
    }
}
