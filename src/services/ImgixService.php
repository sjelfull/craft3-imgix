<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\services;

use Imgix\UrlBuilder;
use superbig\imgix\Imgix;

use Craft;
use craft\base\Component;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixService extends Component
{
    // Public Methods
    // =========================================================================

    protected $builder;

    public function init ()
    {
        parent::init();
        $imgixDomain   = Imgix::$plugin->getSetting('imgixDomain');
        $this->builder = new UrlBuilder($imgixDomain);
    }

    /**
     */
    public function transformImage ($asset = null, $transforms = null, $defaultOptions = [])
    {
        if ( !$asset ) {
            return null;
        }
        $pathsModel = new ImgixModel($asset, $transforms, $defaultOptions);

        return $pathsModel;
    }

    public function getSetting ($setting)
    {
        return craft()->config->get($setting, 'imgix');
    }
}
