<?php
/**
 * Imgix plugin for Craft CMS 5.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\RequestException;

use Imgix\UrlBuilder;
use superbig\imgix\Imgix;
use superbig\imgix\jobs\GenerateTransformsJob;
use superbig\imgix\jobs\PurgeUrlsJob;
use superbig\imgix\models\ImgixModel;
use superbig\imgix\models\Settings;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixService extends Component
{
    // Public Methods
    // =========================================================================

    const IMGIX_PURGE_ENDPOINT_OLD = 'https://api.imgix.com/v2/image/purger';
    const IMGIX_PURGE_ENDPOINT = 'https://api.imgix.com/api/v1/purge';

    protected $builder;

    /**
     * @var Settings
     */
    private $settings;

    public function init(): void
    {
        parent::init();

        $this->settings = Imgix::$plugin->getSettings();
    }

    /**
     * @param null  $asset
     * @param null  $transforms
     * @param array $defaultOptions
     *
     * @return null|ImgixModel
     */
    public function transformImage($asset = null, $transforms = null, $defaultOptions = []): ?ImgixModel
    {
        if (!$asset) {
            return null;
        }
        
        return new ImgixModel($asset, $transforms, $defaultOptions);
    }

    /**
     * @param Asset $asset
     * @param bool $isNew Whether this is a new asset being created
     */
    public function onSaveAsset(Asset $asset, bool $isNew = false)
    {
        $url = $this->getImgixUrl($asset);

        Craft::trace(
            'Getting url: ' . $url,
            __METHOD__
        );

        if ($url) {
            // Only purge on updates, not on new assets
            if (!$isNew) {
                $job = new PurgeUrlsJob();
                $job->urls = [$this->getImgixUrl($asset)];

                Craft::$app->getQueue()->push($job);
            }
            
            // Check if auto-generate is enabled for this asset (both new and updated)
            $this->maybeGenerateTransforms($asset);
        }
    }

    /**
     * @param Asset $asset
     */
    public function onDeleteAsset(Asset $asset)
    {
        $url = $this->getImgixUrl($asset);

        if ($url) {
            $job = new PurgeUrlsJob();
            $job->urls = [$this->getImgixUrl($asset)];

            Craft::$app->getQueue()->push($job);
        }
    }

    /**
     * @param Asset $asset
     *
     * @return bool
     */
    public function purge(Asset $asset)
    {
        $url = $this->getImgixUrl($asset);

        Craft::trace(
            Craft::t(
                'imgix',
                'Purging asset #{id}: {url}', ['id' => $asset->id, 'url' => $url]
            ),
            'imgix');

        return $this->purgeUrl($url);
    }

    /**
     * @param null $url
     *
     * @return bool
     */
    public function purgeUrl($url = null)
    {
        $apiKey = $this->settings->getApiKey();
        $isOldKey = strlen($apiKey) < 50;

        Craft::trace(
            Craft::t(
                'imgix',
                'Purging asset: {url}', ['url' => $url]
            ),
            'imgix');

        try {
            $client = Craft::createGuzzleClient(['timeout' => 30, 'connect_timeout' => 30]);
            $endpoint = $isOldKey ? self::IMGIX_PURGE_ENDPOINT_OLD : self::IMGIX_PURGE_ENDPOINT;
            $config = $isOldKey ? [
                'auth' => [
                    $apiKey, '',
                ],
                'form_params' => [
                    'url' => $url,
                ],
            ] : [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                ],
                'json' => [
                    'data' => [
                        'attributes' => [
                            'url' => $url,
                        ],
                        'type' => 'purges',
                    ],
                ],
            ];
            $response = $client->post($endpoint, $config);

            Craft::trace(
                Craft::t(
                    'imgix',
                    'Purged asset: {url} - Status code {statusCode}', [
                        'url' => $url,
                        'statusCode' => $response->getStatusCode(),
                    ]
                ),
                'imgix');

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        } catch (RequestException $e) {
            Craft::error(
                Craft::t(
                    'imgix',
                    'Failed to purge {url}: {statusCode} {error}', [
                        'url' => $url,
                        'error' => $e->getMessage(),
                        'statusCode' => $e->getResponse()->getStatusCode(),
                    ]
                ),
                'imgix'
            );

            return false;
        }
    }

    public function getImgixUrl(Asset $asset)
    {
        $source = $asset->getVolume();
        $sourceHandle = $source->handle;

        $domains = $this->settings->imgixDomains;
        $domain = array_key_exists($sourceHandle, $domains) ? $domains[ $sourceHandle ] : null;
        $domainParts = [];
        if ($domain !== null) {
            $domainParts = explode('/', $domain, 2);
            $domain = $domainParts[0];
        }

        $assetPath = '';
        if (count($domainParts) === 2) {
            $assetPath = rtrim($domainParts[1], '/') . '/';
        }
        $assetPath .= $asset->getPath();

        if (!isset($domains[ $source->handle ])) {
            return null;
        }

        $builder = new UrlBuilder($domain);
        $builder->setUseHttps(true);
        if ($token = Imgix::$plugin->getSettings()->imgixSignedToken) {
            $builder->setSignKey($token);
        }
        $url = UrlHelper::stripQueryString($builder->createURL($assetPath));

        return $url;
    }

    /**
     * Check if auto-generate is enabled for the asset and queue transform generation
     *
     * @param Asset $asset
     */
    protected function maybeGenerateTransforms(Asset $asset): void
    {
        $autoGenerate = $this->settings->autoGenerate;
        
        // Check if auto-generate is disabled
        if (!$autoGenerate) {
            return;
        }
        
        $volume = $asset->getVolume();
        $volumeHandle = $volume->handle;
        
        // Check if auto-generate is enabled for this volume
        // If autoGenerate is true (boolean), enable for all volumes
        // If autoGenerate is an array, check if this volume is in the list
        $isEnabled = false;
        if (is_bool($autoGenerate) && $autoGenerate === true) {
            $isEnabled = true;
        } elseif (is_array($autoGenerate) && in_array($volumeHandle, $autoGenerate, true)) {
            $isEnabled = true;
        }
        
        if (!$isEnabled) {
            return;
        }
        
        // Get transform definitions
        $transforms = $this->getTransformsForVolume($volumeHandle);
        
        if (empty($transforms)) {
            return;
        }
        
        // Queue the transform generation job
        $job = new GenerateTransformsJob();
        $job->assetId = $asset->id;
        $job->transforms = $transforms;
        
        Craft::$app->getQueue()->push($job);
        
        Craft::trace(
            Craft::t(
                'imgix',
                'Queued {count} transform(s) for asset #{id}',
                ['count' => count($transforms), 'id' => $asset->id]
            ),
            'imgix'
        );
    }

    /**
     * Get transform definitions for a volume
     *
     * @param string $volumeHandle
     * @return array
     */
    protected function getTransformsForVolume(string $volumeHandle): array
    {
        $transformsConfig = $this->settings->transforms;
        
        if (empty($transformsConfig)) {
            return [];
        }
        
        $transforms = [];
        
        // Add volume-specific transforms if they exist
        if (isset($transformsConfig[$volumeHandle]) && is_array($transformsConfig[$volumeHandle])) {
            $transforms = array_merge($transforms, $transformsConfig[$volumeHandle]);
        }
        
        // Add global transforms if they exist
        if (isset($transformsConfig['global']) && is_array($transformsConfig['global'])) {
            $transforms = array_merge($transforms, $transformsConfig['global']);
        }
        
        return $transforms;
    }
}
