<?php
/**
 * Imgix plugin for Craft CMS 5.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\elements\Asset;
use superbig\imgix\Imgix;

/**
 * GenerateTransformsJob generates imgix transforms for an asset
 *
 * @author    Superbig
 * @package   Imgix
 * @since     4.1.0
 */
class GenerateTransformsJob extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Asset ID to generate transforms for
     *
     * @var int
     */
    public $assetId;

    /**
     * Transform definitions to generate
     *
     * @var array
     */
    public $transforms = [];

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $asset = Asset::find()->id($this->assetId)->one();

        if (!$asset) {
            return;
        }

        $totalSteps = count($this->transforms);

        for ($step = 0; $step < $totalSteps; ++$step) {
            $this->setProgress($queue, $step / $totalSteps);
            $transform = $this->transforms[$step];

            // Generate the imgix URL - this will cause imgix to create the transform on first request
            $imgixModel = Imgix::$plugin->imgixService->transformImage($asset, $transform);
            
            if ($imgixModel) {
                $url = $imgixModel->getUrl();
                
                // Optionally, we could make a HEAD request to warm the cache
                // For now, just generating the URL is sufficient as imgix will create it on first access
                Craft::trace(
                    Craft::t(
                        'imgix',
                        'Generated transform for asset #{id}: {url}',
                        ['id' => $asset->id, 'url' => $url]
                    ),
                    'imgix'
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('imgix', 'Generating {count} transform(s)', [
            'count' => count($this->transforms),
        ]);
    }
}
