<?php
/**
 * Imgix plugin for Craft CMS 3.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\models;

use Craft;
use craft\base\Model;
use craft\elements\Asset;
use craft\helpers\Html;
use craft\helpers\Template;
use Imgix\UrlBuilder;
use superbig\imgix\Imgix;
use Twig\Markup;
use yii\base\Exception;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixModel extends Model
{
    public $transformed = [];
    protected $supportedAttributes = [
        'bri',
        'con',
        'exp',
        'gam',
        'high',
        'hue',
        'invert',
        'sat',
        'shad',
        'sharp',
        'usm',
        'usmrad',
        'vib',
        'auto',
        'bg',
        'blend',
        'ba',
        'balph',
        'bc',
        'bf',
        'bh',
        'bm',
        'bp',
        'bs',
        'bw',
        'bx',
        'by',
        'border',
        'border-radius-inner',
        'border-radius',
        'pad',
        'prefix',
        'palette',
        'colors',
        'dpr',
        'faceindex',
        'facepad',
        'faces',
        'fp-debug',
        'fp-z',
        'fp-x',
        'fp-y',
        'chromasub',
        'ch',
        'colorquant',
        'cs',
        'dpi',
        'dl',
        'lossless',
        'fm',
        'q',
        'corner-radius',
        'maskbg',
        'mask',
        'nr',
        'nrs',
        'page',
        'flip',
        'or',
        'rot',
        'crop',
        'h',
        'w',
        'max-h',
        'max-w',
        'min-h',
        'min-w',
        'fit',
        'rect',
        'blur',
        'htn',
        'mono',
        'px',
        'sepia',
        'txtalign',
        'txtclip',
        'txtclr',
        'txtfit',
        'txtfont',
        'txtsize',
        'txtlig',
        'txtline',
        'txtlineclr',
        'txtpad',
        'txtshad',
        'txt',
        'txtwidth',
        'trimcolor',
        'trim',
        'trimmd',
        'trimsd',
        'trimtol',
        'txtlead',
        'txttrack',
        '~text',
        'markalign',
        'markalpha',
        'markbase',
        'markfit',
        'markh',
        'mark',
        'markpad',
        'markscale',
        'markw',
        'markx',
        'marky',
    ];
    protected $attributesTranslate = [
        'width' => 'w',
        'height' => 'h',
        'min-width' => 'min-w',
        'max-width' => 'max-w',
        'min-height' => 'min-h',
        'max-height' => 'max-h',
        'x' => 'fp-x',
        'y' => 'fp-y',
    ];
    protected $transforms;
    protected $imagePath;
    protected $builder;
    protected $defaultOptions;
    protected $lazyLoadPrefix;

    public function __construct($image, $transforms = null, $defaultOptions = [])
    {
        parent::__construct();
        $this->lazyLoadPrefix = Imgix::$plugin->getSettings()->lazyLoadPrefix ?: 'data-';

        /** @var null|Asset $image */
        if ($image instanceof Asset) {
            $source = $image->getVolume();
            $sourceHandle = $source->handle;
            $focalPoint = $image->getFocalPoint();
            $domains = Imgix::$plugin->getSettings()->imgixDomains;
            $domain = array_key_exists($sourceHandle, $domains) ? $domains[ $sourceHandle ] : null;
            $domainParts = [];

            if ($domain === null) {
                // Domain isn't in imgixDomains, just passthrough the image
                $this->transformed = $image;

                return;
            }

            $domainParts = explode('/', $domain, 2);
            $domain = $domainParts[0];

            $this->builder = new UrlBuilder($domain);
            $this->builder->setUseHttps(true);

            if ($token = Imgix::$plugin->getSettings()->imgixSignedToken) {
                $this->builder->setSignKey($token);
            }

            $imagePath = '';
            if (count($domainParts) === 2) {
                $imagePath = rtrim($domainParts[1], '/') . '/';
            }
            $imagePath .= $image->getPath();

            $this->imagePath = $imagePath;
            $this->transforms = $transforms;

            if (!empty($focalPoint)) {
                $defaultOptions['x'] = $focalPoint['x'];
                $defaultOptions['y'] = $focalPoint['y'];
            }

            $this->defaultOptions = $defaultOptions;

            $this->transform($transforms);

            return;
        }

        if (gettype($image) === 'string') {
            $domains = Imgix::$plugin->getSettings()->imgixDomains;
            $firstHandle = reset($domains);
            $domain = $domains[ $firstHandle ];
            $domainParts = [];
            if ($domain !== null) {
                $domainParts = explode('/', $domain, 2);
                $domain = $domainParts[0];
            }

            $this->builder = new UrlBuilder($domain);
            $this->builder->setUseHttps(true);

            if ($token = Imgix::$plugin->getSettings()->imgixSignedToken) {
                $this->builder->setSignKey($token);
            }

            $imagePath = '';
            if (count($domainParts) === 2) {
                $imagePath = rtrim($domainParts[1], '/') . '/';
            }
            $imagePath .= $image;

            $this->imagePath = $imagePath;
            $this->transforms = $transforms;
            $this->defaultOptions = $defaultOptions;
            $this->transform($transforms);

            return;
        }

        throw new Exception(Craft::t('An unknown image object was used.'));
    }

    public function img($attributes = null): ?Markup
    {
        $image = $this->transformed;

        if (!$image || !isset($image['url'])) {
            return null;
        }

        $lazyLoad = false;
        if (isset($attributes['lazyLoad'])) {
            $lazyLoad = $attributes['lazyLoad'];
            unset($attributes['lazyLoad']); // unset to remove it from the html output
        }
        $lazyLoadPrefix = $lazyLoad ? $this->lazyLoadPrefix : '';
        $srcKey = $lazyLoadPrefix . 'src';

        return Template::raw(
            Html::tag('img', '', array_merge(
                [$srcKey => $image['url']],
                ($attributes ?? []),
            ))
        );
    }

    /**
     * @return mixed|null
     */
    public function getUrl()
    {
        if ($image = $this->transformed) {
            if ($image && isset($image['url'])) {
                return $image['url'];
            }
        }

        return null;
    }

    public function srcset($attributes = []): ?Markup
    {
        if (empty($this->transformed)) {
            return null;
        }

        $images = $this->transformed;
        $widths = [];
        $srcsetParts = [];
        $firstSrc = '';

        if ($images instanceof Asset) {
            $width = $images->getWidth();
            $firstSrc = $images->getUrl();
            $srcsetParts[] = $images->getUrl() . ' ' . $width . 'w';
        }
        else {
            $firstSrc = $images[0]['url'];
            foreach ($images as $image) {
                $width = $image['width'] ?? $image['w'] ?? null;
                if ($width && !isset($widths[ $width ])) {
                    $withs[ $width ] = true;
                    $srcsetParts[] = $image['url'] . ' ' . $width . 'w';
                }
            }
        }

        $srcset = implode(', ', $srcsetParts);
        $lazyLoad = false;

        if (isset($attributes['lazyLoad'])) {
            $lazyLoad = $attributes['lazyLoad'];
            unset($attributes['lazyLoad']); // unset to remove it from the html output
        }

        $lazyLoadPrefix = $lazyLoad ? $this->lazyLoadPrefix : '';
        $srcKey = $lazyLoadPrefix . 'src';
        $srcsetKey = $lazyLoadPrefix . 'srcset';

        return Template::raw(
            Html::tag('img', '', array_merge([
                $srcKey => $firstSrc,
                $srcsetKey => $srcset,
            ], $attributes ?? []))
        );
    }

    /**
     * @param $transforms
     *
     * @return null
     */
    protected function transform($transforms)
    {
        if (!$transforms) {
            return null;
        }
        if (isset($transforms[0])) {
            $images = [];

            foreach ($transforms as $transform) {
                $transform = array_merge($this->defaultOptions, $transform);
                $transform = $this->calculateTargetSizeFromRatio($transform);
                $url = $this->buildTransform($this->imagePath, $transform);
                $images[] = array_merge($transform, ['url' => $url]);
            }

            $this->transformed = $images;
        }
        else {
            $transforms = array_merge($this->defaultOptions, $transforms);
            $transforms = $this->calculateTargetSizeFromRatio($transforms);
            $url = $this->buildTransform($this->imagePath, $transforms);
            $image = array_merge($transforms, ['url' => $url]);
            $this->transformed = $image;
        }
    }

    /**
     * @param $filename
     * @param $transform
     *
     * @return string
     */
    private function buildTransform($filename, $transform)
    {
        $parameters = $this->translateAttributes($transform);

        return $this->builder->createURL($filename, $parameters);
    }

    /**
     * @param $attributes
     *
     * @return array
     */
    private function translateAttributes($attributes)
    {
        $translatedAttributes = [];

        foreach ($attributes as $key => $setting) {
            if (array_key_exists($key, $this->attributesTranslate)) {
                $key = $this->attributesTranslate[ $key ];
            }

            $translatedAttributes[ $key ] = $setting;
        }

        return $translatedAttributes;
    }

    /**
     * @param $transform
     *
     * @return mixed
     */
    protected function calculateTargetSizeFromRatio($transform)
    {
        if (!isset($transform['ratio'])) {
            return $transform;
        }

        $ratio = (float)$transform['ratio'];
        $w = isset($transform['w']) ? $transform['w'] : null;
        $h = isset($transform['h']) ? $transform['h'] : null;

        // If both sizes and ratio is specified, let ratio take control based on width
        if ($w and $h) {
            $transform['h'] = round($w / $ratio);
        }
        else {
            if ($w) {
                $transform['h'] = round($w / $ratio);
            }
            elseif ($h) {
                $transform['w'] = round($h * $ratio);
            }
            else {
                // TODO: log that neither w nor h is specified with ratio
                // no idea what to do, return
                return $transform;
            }
        }

        unset($transform['ratio']); // remove the ratio setting so that it doesn't gets processed in the URL

        return $transform;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['transformed', 'array'],
            ['transformed', 'default', 'value' => []],
        ];
    }
}
