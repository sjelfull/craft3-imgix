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

use craft\base\Element;
use craft\elements\Asset;
use craft\events\ElementEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\ReplaceAssetEvent;
use craft\services\Assets;
use craft\services\Elements;
use superbig\imgix\actions\ImgixPurgeAction;
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
 * @method  Settings getSettings()
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

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            function (ElementEvent $event) {
                Craft::trace(
                    'Elements::EVENT_BEFORE_SAVE_ELEMENT',
                    __METHOD__
                );

                /** @var Element $element */
                $element      = $event->element;
                $isNewElement = $event->isNew;

                if ( $element instanceof Asset && !$isNewElement ) {
                    Imgix::$plugin->imgixService->onSaveAsset($element);
                }
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (ElementEvent $event) {
                Craft::trace(
                    'Elements::EVENT_BEFORE_DELETE_ELEMENT',
                    __METHOD__
                );

                /** @var Element $element */
                $element      = $event->element;
                $isNewElement = $event->isNew;

                if ( $element instanceof Asset ) {
                    Imgix::$plugin->imgixService->onDeleteAsset($element);
                }
            }
        );

        Event::on(
            Assets::class,
            Assets::EVENT_BEFORE_REPLACE_ASSET,
            function (ReplaceAssetEvent $event) {
                Craft::trace(
                    'Assets::EVENT_BEFORE_REPLACE_ASSET',
                    __METHOD__
                );
                /** @var Asset $element */
                $element = $event->asset;

                Imgix::$plugin->imgixService->onSaveAsset($element);
            }
        );

        Event::on(
            Asset::class,
            Element::EVENT_REGISTER_ACTIONS,
            function (RegisterElementActionsEvent $event) {
                $event->actions[] = new ImgixPurgeAction();
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
