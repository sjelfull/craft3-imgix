<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\services;

use craft\elements\Asset;
use craft\helpers\UrlHelper;
use craft\helpers\Assets as AssetsHelper;
use GuzzleHttp\Exception\RequestException;
use Imgix\UrlBuilder;
use superbig\imgix\Imgix;

use Craft;
use craft\base\Component;
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

    const IMGIX_PURGE_ENDPOINT = 'https://api.imgix.com/v2/image/purger';

    protected $builder;

    /**
     * @var Settings
     */
    private $settings;

    public function init ()
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
    public function transformImage ($asset = null, $transforms = null, $defaultOptions = [])
    {
        if ( !$asset ) {
            return null;
        }
        $pathsModel = new ImgixModel($asset, $transforms, $defaultOptions);

        return $pathsModel;
    }

    /**
     * @param Asset $asset
     */
    public function onSaveAsset (Asset $asset)
    {
        $url = $this->getImgixUrl($asset);

        Craft::trace(
            'Getting url: ' . $url,
            __METHOD__
        );

        if ( $url ) {
            $job       = new PurgeUrlsJob();
            $job->urls = [ $this->getImgixUrl($asset) ];

            Craft::$app->getQueue()->push($job);
        }
    }

    /**
     * @param Asset $asset
     */
    public function onDeleteAsset (Asset $asset)
    {
        $url = $this->getImgixUrl($asset);

        if ( $url ) {
            $job       = new PurgeUrlsJob();
            $job->urls = [ $this->getImgixUrl($asset) ];

            Craft::$app->getQueue()->push($job);
        }
    }

    /**
     * @param Asset $asset
     *
     * @return bool
     */
    public function purge (Asset $asset)
    {
        $url = $this->getImgixUrl($asset);

        Craft::trace(
            Craft::t(
                'imgix',
                'Purging asset #{id}: {url}', [ 'id' => $asset->id, 'url' => $url ]
            ),
            'imgix');

        return $this->purgeUrl($url);
    }

    /**
     * @param null $url
     *
     * @return bool
     */
    public function purgeUrl ($url = null)
    {
        $apiKey = $this->settings->apiKey;

        Craft::trace(
            Craft::t(
                'imgix',
                'Purging asset: {url}', [ 'url' => $url ]
            ),
            'imgix');

        try {
            $client = Craft::createGuzzleClient([ 'timeout' => 30, 'connect_timeout' => 30 ]);

            $response = $client->post(self::IMGIX_PURGE_ENDPOINT, [
                'auth'        => [
                    $apiKey, ''
                ],
                'form_params' => [
                    'url' => $url,
                ]
            ]);

            Craft::trace(
                Craft::t(
                    'imgix',
                    'Purged asset: {url} - Status code {statusCode}', [
                        'url'        => $url,
                        'statusCode' => $response->getStatusCode()
                    ]
                ),
                'imgix');

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        }
        catch (RequestException $e) {
            Craft::error(
                Craft::t(
                    'imgix',
                    'Failed to purge {url}: {statusCode} {error}', [
                        'url'        => $url,
                        'error'      => $e->getMessage(),
                        'statusCode' => $e->getResponse()->getStatusCode() ]
                ),
                'imgix'
            );

            return false;
        }
    }

    /**
     * @param Asset $asset
     *
     * @return null|string
     */
    public function getImgixUrl (Asset $asset)
    {
        $url      = null;
        $domains  = $this->settings->imgixDomains;
        $volume   = $asset->getVolume();
        $assetUrl = AssetsHelper::generateUrl($volume, $asset);
        $assetUri = parse_url($assetUrl, PHP_URL_PATH);

        if ( isset($domains[ $volume->handle ]) ) {
            $builder = new UrlBuilder($domains[ $volume->handle ]);
            $builder->setUseHttps(true);
            if ($token = Imgix::$plugin->getSettings()->imgixSignedToken)
                $builder->setSignKey($token);
            $url = UrlHelper::stripQueryString($builder->createURL($assetUri));
        }


        return $url;
    }

}
