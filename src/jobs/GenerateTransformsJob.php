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
     * Whether to warm the imgix cache by making HTTP requests
     *
     * @var bool
     */
    public $warmCache = false;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $asset = Asset::find()->id($this->assetId)->one();

        if (!$asset) {
            Craft::warning(
                Craft::t(
                    'imgix',
                    'Asset #{id} not found for transform generation',
                    ['id' => $this->assetId]
                ),
                'imgix'
            );
            return;
        }

        $totalSteps = count($this->transforms);
        
        // Create Guzzle client once if cache warming is enabled
        $client = null;
        if ($this->warmCache) {
            $client = Craft::createGuzzleClient(['timeout' => 10, 'connect_timeout' => 10]);
        }

        for ($step = 0; $step < $totalSteps; ++$step) {
            $this->setProgress($queue, $step / $totalSteps);
            $transform = $this->transforms[$step];

            // Generate the imgix URL - this will cause imgix to create the transform on first request
            $imgixModel = Imgix::$plugin->imgixService->transformImage($asset, $transform);
            
            if ($imgixModel) {
                $url = $imgixModel->getUrl();
                
                Craft::trace(
                    Craft::t(
                        'imgix',
                        'Generated transform for asset #{id}: {url}',
                        ['id' => $asset->id, 'url' => $url]
                    ),
                    'imgix'
                );
                
                // Optionally warm the cache by making a HEAD request
                if ($this->warmCache && $url && $client) {
                    try {
                        $client->head($url);
                        
                        Craft::trace(
                            Craft::t(
                                'imgix',
                                'Warmed cache for transform: {url}',
                                ['url' => $url]
                            ),
                            'imgix'
                        );
                    } catch (\Exception $e) {
                        // Silently fail - cache warming is optional
                        Craft::warning(
                            Craft::t(
                                'imgix',
                                'Failed to warm cache for {url}: {error}',
                                ['url' => $url, 'error' => $e->getMessage()]
                            ),
                            'imgix'
                        );
                    }
                }
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
