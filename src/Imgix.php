<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix;

use superbig\imgix\models\Settings;
use superbig\imgix\services\ImgixService as ImgixServiceService;
use superbig\imgix\variables\ImgixVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

/**
 * Class Imgix
 *
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 *
 * @property  ImgixServiceService $imgixService
 */
class Imgix extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Imgix
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init ()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('imgix', ImgixVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'imgix',
                '{name} plugin loaded',
                [ 'name' => $this->name ]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel ()
    {
        return new Settings();
    }
}
