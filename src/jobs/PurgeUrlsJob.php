<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\jobs;

use craft\elements\Asset;
use craft\queue\BaseJob;
use Imgix\UrlBuilder;
use superbig\imgix\Imgix;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class PurgeUrlsJob extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * URLs to purge
     *
     * @var array
     */
    public $urls = [];

    public function execute ($queue)
    {
        $totalSteps = count($this->urls);

        for ($step = 0; $step < $totalSteps; $step++) {
            $this->setProgress($queue, $step / $totalSteps);
            $url = $this->urls[ $step ];

            Imgix::$plugin->imgixService->purgeUrl($url);
        }
    }

    protected function defaultDescription ()
    {
        return count($this->urls) > 1 ? 'Purging images' : 'Purging image';
    }
}