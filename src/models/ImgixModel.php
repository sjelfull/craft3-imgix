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

use craft\elements\Asset;
use craft\helpers\Template;
use Imgix\UrlBuilder;
use superbig\imgix\Imgix;

use Craft;
use craft\base\Model;
use yii\base\Exception;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class ImgixModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var array
     */
    public $transformed = [];

    // Protected Properties
    // =========================================================================

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
        'width'      => 'w',
        'height'     => 'h',
        'min-width'  => 'min-w',
        'max-width'  => 'max-w',
        'min-height' => 'min-h',
        'max-height' => 'max-h',
        'x'          => 'fp-x',
        'y'          => 'fp-y',
    ];
    protected $transforms;
    protected $imagePath;
    protected $builder;
    protected $defaultOptions;
    protected $lazyLoadPrefix;

    // Public Methods
    // =========================================================================


    /**
     * Constructor
     *
     * @param $image
     *
     * @throws Exception
     */
    public function __construct($image, $transforms = null, $defaultOptions = [])
    {
        parent::__construct();
        $this->lazyLoadPrefix = Imgix::$plugin->getSettings()->lazyLoadPrefix ?: 'data-';

        /** @var null|Asset $image */
        if ($image instanceof Asset) {
            $source       = $image->getVolume();
            $sourceHandle = $source->handle;
            $focalPoint   = $image->getFocalPoint();

            $domains = Imgix::$plugin->getSettings()->imgixDomains;
            $domain  = array_key_exists($sourceHandle, $domains) ? $domains[ $sourceHandle ] : null;

            $this->builder = new UrlBuilder($domain);
            $this->builder->setUseHttps(true);

            if ($token = Imgix::$plugin->getSettings()->imgixSignedToken)
                $this->builder->setSignKey($token);

            $this->imagePath  = $image->getPath();
            $this->transforms = $transforms;

            if (!empty($focalPoint)) {
                $defaultOptions['fp-x'] = $focalPoint['x'];
                $defaultOptions['fp-y'] = $focalPoint['y'];
            }

            $this->defaultOptions = $defaultOptions;

            $this->transform($transforms);
        }
        elseif (gettype($image) === 'string') {
            $domains     = Imgix::$plugin->getSettings()->imgixDomains;
            $firstHandle = reset($domains);
            $domain      = $domains[ $firstHandle ];

            $this->builder = new UrlBuilder($domain);
            $this->builder->setUseHttps(true);

            if ($token = Imgix::$plugin->getSettings()->imgixSignedToken)
                $this->builder->setSignKey($token);

            $this->imagePath      = $image;
            $this->transforms     = $transforms;
            $this->defaultOptions = $defaultOptions;
            $this->transform($transforms);
        }
        else {
            throw new Exception(Craft::t('An unknown image object was used.'));
        }
    }

    /**
     * @param null $attributes
     *
     * @return null|\Twig_Markup
     */
    public function img($attributes = null)
    {
        if ($image = $this->transformed) {
            if ($image && isset($image['url'])) {
                $lazyLoad = false;
                if (isset($attributes['lazyLoad'])) {
                    $lazyLoad = $attributes['lazyLoad'];
                    unset($attributes['lazyLoad']); // unset to remove it from the html output
                }
                $tagAttributes = $this->getTagAttributes($attributes);

                return Template::raw('<img ' . ($lazyLoad ? $this->lazyLoadPrefix : '') . 'src="' . $image['url'] . '" ' . $tagAttributes . ' />');
            }
        }

        return null;
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

    /**
     * @param $attributes
     *
     * @return null|\Twig_Markup
     */
    public function srcset($attributes = [])
    {
        if ($images = $this->transformed) {
            $widths = [];
            $result = '';

            foreach ($images as $image) {
                $keys  = array_keys($image);
                $width = $image['width'] ?? $image['w'] ?? null;
                if ($width && !isset($widths[ $width ])) {
                    $withs[ $width ] = true;
                    $result          .= $image['url'] . ' ' . $width . 'w, ';
                }
            }

            $srcset   = substr($result, 0, strlen($result) - 2);
            $lazyLoad = false;

            if (isset($attributes['lazyLoad'])) {
                $lazyLoad = $attributes['lazyLoad'];
                unset($attributes['lazyLoad']); // unset to remove it from the html output
            }

            $tagAttributes = $this->getTagAttributes($attributes);

            return Template::raw('<img ' . ($lazyLoad ? $this->lazyLoadPrefix : '') . 'src="' . $images[0]['url'] . '" ' . ($lazyLoad ? $this->lazyLoadPrefix : '') . 'srcset="' . $srcset . '" ' . $tagAttributes . ' />');
        }

        return null;
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
                $transform = array_merge($transform, $this->defaultOptions);
                $transform = $this->calculateTargetSizeFromRatio($transform);
                $url       = $this->buildTransform($this->imagePath, $transform);
                $images[]  = array_merge($transform, ['url' => $url]);
            }

            $this->transformed = $images;
        }
        else {
            $transforms        = array_merge($transforms, $this->defaultOptions);
            $transforms        = $this->calculateTargetSizeFromRatio($transforms);
            $url               = $this->buildTransform($this->imagePath, $transforms);
            $image             = array_merge($transforms, ['url' => $url]);
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
     * @param $attributes
     *
     * @return string
     */
    private function getTagAttributes($attributes)
    {
        if (!$attributes) {
            return '';
        }

        $tagAttributes = '';

        foreach ($attributes as $key => $attribute) {
            $tagAttributes .= ' ' . $key . '="' . $attribute . '"';
        }

        return $tagAttributes;
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
        $w     = isset($transform['w']) ? $transform['w'] : null;
        $h     = isset($transform['h']) ? $transform['h'] : null;

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
    public function rules()
    {
        return [
            ['transformed', 'array'],
            ['transformed', 'default', 'value' => []],
        ];
    }
}
