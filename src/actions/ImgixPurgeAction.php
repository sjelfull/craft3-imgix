<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

use superbig\imgix\Imgix;
use superbig\imgix\jobs\PurgeUrlsJob;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixPurgeAction extends ElementAction
{
    // Public Properties
    // =========================================================================

    /**
     * URLs to purge
     *
     * @var array
     */
    public $urls = [];

    public function getTriggerLabel(): string
    {
        return Craft::t('imgix', 'Imgix Purge');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        foreach ($query->all() as $asset) {
            $this->urls[] = Imgix::$plugin->imgixService->getImgixUrl($asset);
        }

        $this->setMessage(
            Craft::t(
                'imgix',
                'Purging images'
            )
        );

        $job = new PurgeUrlsJob();
        $job->urls = $this->urls;

        Craft::$app->getQueue()->push($job);

        return true;
    }
}
