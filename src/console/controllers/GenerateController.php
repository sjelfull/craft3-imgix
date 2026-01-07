<?php
/**
 * Imgix plugin for Craft CMS 5.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Asset;
use craft\helpers\Console;
use superbig\imgix\Imgix;
use superbig\imgix\jobs\GenerateTransformsJob;
use yii\console\ExitCode;

/**
 * Generate transforms for assets
 *
 * @author    Superbig
 * @package   Imgix
 * @since     4.1.0
 */
class GenerateController extends Controller
{
    /**
     * @var string|null Volume handle to limit generation to
     */
    public $volume;

    /**
     * @var int|null Limit the number of assets to process
     */
    public $limit;

    /**
     * @var bool Whether to warm the imgix cache
     */
    public $warmCache = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'volume';
        $options[] = 'limit';
        $options[] = 'warmCache';
        return $options;
    }

    /**
     * Generate transforms for assets
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $settings = Imgix::$plugin->getSettings();

        if (empty($settings->transforms)) {
            $this->stdout("No transforms configured.\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }

        // Build asset query
        $query = Asset::find();

        if ($this->volume) {
            $query->volume($this->volume);
            $this->stdout("Processing assets from volume: {$this->volume}\n", Console::FG_CYAN);
        } else {
            $this->stdout("Processing all assets\n", Console::FG_CYAN);
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->stdout("No assets found.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("Found {$total} asset(s) to process.\n", Console::FG_GREEN);

        $processed = 0;
        $queued = 0;

        foreach ($query->each() as $asset) {
            /** @var Asset $asset */
            $processed++;

            $volume = $asset->getVolume();
            $volumeHandle = $volume->handle;

            // Get transforms for this volume
            $transforms = Imgix::$plugin->imgixService->getTransformsForVolumePublic($volumeHandle);

            if (empty($transforms)) {
                $this->stdout("[{$processed}/{$total}] Skipping {$asset->filename} - no transforms configured for volume '{$volumeHandle}'\n", Console::FG_GREY);
                continue;
            }

            // Queue the transform generation job
            $job = new GenerateTransformsJob();
            $job->assetId = $asset->id;
            $job->transforms = $transforms;
            $job->warmCache = $this->warmCache || $settings->warmCache;

            Craft::$app->getQueue()->push($job);
            $queued++;

            $this->stdout("[{$processed}/{$total}] Queued {$asset->filename} with " . count($transforms) . " transform(s)\n", Console::FG_GREEN);
        }

        $this->stdout("\nDone! Processed {$processed} asset(s), queued {$queued} job(s).\n", Console::FG_GREEN);

        if ($queued > 0) {
            $this->stdout("Run 'php craft queue/run' to process the queue.\n", Console::FG_CYAN);
        }

        return ExitCode::OK;
    }

    /**
     * Generate transforms for a specific asset by ID
     *
     * @param int $assetId The asset ID
     * @param string|null $transforms Comma-separated list of named transforms or 'all' for configured transforms
     * @return int
     */
    public function actionAsset(int $assetId, ?string $transforms = null): int
    {
        $settings = Imgix::$plugin->getSettings();
        $asset = Asset::find()->id($assetId)->one();

        if (!$asset) {
            $this->stdout("Asset #{$assetId} not found.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $volume = $asset->getVolume();
        $volumeHandle = $volume->handle;

        $this->stdout("Processing asset: {$asset->filename} (ID: {$assetId})\n", Console::FG_CYAN);

        // Determine which transforms to use
        $transformsToGenerate = [];

        if ($transforms && $transforms !== 'all') {
            // Use specified named transforms
            $namedTransforms = explode(',', $transforms);
            foreach ($namedTransforms as $namedTransform) {
                $namedTransform = trim($namedTransform);
                $transform = $settings->getNamedTransform($namedTransform);
                if ($transform) {
                    $transformsToGenerate[] = $transform;
                    $this->stdout("  Added named transform: {$namedTransform}\n", Console::FG_GREEN);
                } else {
                    $this->stdout("  Named transform '{$namedTransform}' not found\n", Console::FG_YELLOW);
                }
            }
        } else {
            // Use configured transforms for this volume
            $transformsToGenerate = Imgix::$plugin->imgixService->getTransformsForVolumePublic($volumeHandle);
        }

        if (empty($transformsToGenerate)) {
            $this->stdout("No transforms to generate.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        // Queue the transform generation job
        $job = new GenerateTransformsJob();
        $job->assetId = $asset->id;
        $job->transforms = $transformsToGenerate;
        $job->warmCache = $this->warmCache || $settings->warmCache;

        Craft::$app->getQueue()->push($job);

        $this->stdout("Queued " . count($transformsToGenerate) . " transform(s) for {$asset->filename}\n", Console::FG_GREEN);
        $this->stdout("Run 'php craft queue/run' to process the queue.\n", Console::FG_CYAN);

        return ExitCode::OK;
    }
}
