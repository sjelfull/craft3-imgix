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

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\events\ElementEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\ReplaceAssetEvent;
use craft\helpers\ElementHelper;
use craft\services\Assets;
use craft\services\Elements;
use craft\web\twig\variables\CraftVariable;
use superbig\imgix\actions\ImgixPurgeAction;

use superbig\imgix\models\Settings;
use superbig\imgix\services\ImgixService as ImgixServiceService;
use superbig\imgix\variables\ImgixVariable;

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
    /**
     * @var Imgix
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function (Event $event) : void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('imgix', ImgixVariable::class);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            static function (ElementEvent $event) : void {
                $element = $event->element;
                $isNewElement = $event->isNew;
                if ($element instanceof Asset && !$isNewElement) {
                    Imgix::$plugin->imgixService->onSaveAsset($element);
                }
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            static function (ElementEvent $event) : void {
                $element = $event->element;
                if ($element instanceof Asset) {
                    Imgix::$plugin->imgixService->onDeleteAsset($element);
                }
            }
        );

        Event::on(
            Assets::class,
            Assets::EVENT_BEFORE_REPLACE_ASSET,
            static function (ReplaceAssetEvent $event) : void {
                $element = $event->asset;
                Imgix::$plugin->imgixService->onSaveAsset($element);
            }
        );

        Event::on(
            Asset::class,
            Element::EVENT_REGISTER_ACTIONS,
            static function (RegisterElementActionsEvent $event) : void {
                $event->actions[] = new ImgixPurgeAction();
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): \superbig\imgix\models\Settings
    {
        return new Settings();
    }
}
