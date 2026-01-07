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
use GuzzleHttp\Exception\GuzzleException;
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
    // Constants
    // =========================================================================
    
    /**
     * HTTP request timeout in seconds for cache warming
     */
    const CACHE_WARM_TIMEOUT = 10;
    
    /**
     * Maximum number of transforms before throttling progress updates
     */
    const PROGRESS_UPDATE_THRESHOLD = 10;
    
    /**
     * Progress update frequency (every N transforms)
     */
    const PROGRESS_UPDATE_FREQUENCY = 5;
    
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
            $client = Craft::createGuzzleClient([
                'timeout' => self::CACHE_WARM_TIMEOUT,
                'connect_timeout' => self::CACHE_WARM_TIMEOUT,
            ]);
        }

        for ($step = 0; $step < $totalSteps; ++$step) {
            // Update progress - use modulo to reduce frequency for large transform sets
            if ($totalSteps <= self::PROGRESS_UPDATE_THRESHOLD || 
                $step % self::PROGRESS_UPDATE_FREQUENCY === 0 || 
                $step === $totalSteps - 1) {
                $this->setProgress($queue, ($step + 1) / $totalSteps);
            }
            
            $transform = $this->transforms[$step];

            // Generate the imgix URL - this will cause imgix to create the transform on first request
            $imgixModel = Imgix::$plugin->imgixService->transformImage($asset, $transform);
            
            if (!$imgixModel) {
                Craft::warning(
                    Craft::t(
                        'imgix',
                        'Failed to create imgix model for asset #{id} with transform: {transform}',
                        ['id' => $asset->id, 'transform' => json_encode($transform)]
                    ),
                    'imgix'
                );
                continue;
            }
            
            $url = $imgixModel->getUrl();
            
            if (!$url) {
                Craft::warning(
                    Craft::t(
                        'imgix',
                        'Failed to generate URL for asset #{id} with transform: {transform}',
                        ['id' => $asset->id, 'transform' => json_encode($transform)]
                    ),
                    'imgix'
                );
                continue;
            }
            
            Craft::trace(
                Craft::t(
                    'imgix',
                    'Generated transform for asset #{id}: {url}',
                    ['id' => $asset->id, 'url' => $url]
                ),
                'imgix'
            );
            
            // Optionally warm the cache by making a HEAD request
            if ($this->warmCache && $client) {
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
                } catch (GuzzleException $e) {
                    // Log but don't fail - cache warming is optional
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
