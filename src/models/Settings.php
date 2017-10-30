<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\models;

use craft\elements\Asset;
use Imgix\UrlBuilder;
use superbig\imgix\Imgix;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $imgixDomains = [];

    /**
     * @var string
     */
    public $lazyLoadPrefix = '';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules ()
    {
        return [
            [ 'imgixDomains', 'array' ],
            [ 'imgixDomains', 'default', 'value' => [] ],
            [ 'lazyLoadPrefix', 'string' ],
            [ 'lazyLoadPrefix', 'default', 'value' => '' ],
        ];
    }
}
