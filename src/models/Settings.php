<?php
/**
 * Imgix plugin for Craft CMS 5.x
 *
 * Use Imgix with Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\imgix\models;

use Craft;

use craft\base\Model;
use superbig\imgix\Imgix;

/**
 * @author    Superbig
 * @package   Imgix
 * @since     2.0.0
 */
class Settings extends Model
{
    public function init(): void
    {
        parent::init();

        if (!empty($this->apiKey) && strlen($this->apiKey) < 50) {
            \Craft::$app->deprecator->log(__METHOD__, 'You appear to be using an API key for v1 of the Imgix API. v1 has been deprecated. You need to generate a new one from https://dashboard.imgix.com/api-keys/new, with permissions to purge, and replace the old one. See https://blog.imgix.com/2020/10/16/api-deprecation for more information.');
        }
    }

    /**
     * Imgix API key
     *
     * @var string
     */
    public $apiKey = '';

    /**
     * Volume handles mapped to Imgix domains
     *
     * @var array
     */
    public $imgixDomains = [];

    /**
     * Imgix signed URLs token
     *
     * @var string
     */
    public $imgixSignedToken = '';

    /**
     * @var string
     */
    public $lazyLoadPrefix = '';

    /**
     * Auto-generate transforms on asset upload/save
     * Can be a boolean (true/false) or an array of volume handles
     *
     * @var bool|array
     */
    public $autoGenerate = false;

    /**
     * Whether to warm imgix cache by making HTTP requests
     *
     * @var bool
     */
    public $warmCache = false;

    /**
     * Transform definitions to generate automatically
     * Can be defined globally or per volume handle
     *
     * @var array
     */
    public $transforms = [];

    public function getApiKey()
    {
        $apiKey = Craft::parseEnv($this->apiKey);

        if (!empty($apiKey) && strlen($apiKey) < 50) {
            \Craft::$app->deprecator->log(__METHOD__, 'You appear to be using an deprecated API key for the Imgix API. You need to generate a new one from https://dashboard.imgix.com/api-keys/new, with permissions to purge, and replace the old one. See https://blog.imgix.com/2020/10/16/api-deprecation for more information.');
        }

        return $apiKey;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['imgixDomains', 'array'],
            ['imgixDomains', 'default', 'value' => []],
            ['imgixSignedToken', 'string'],
            ['imgixSignedToken', 'default', 'value' => ''],
            ['lazyLoadPrefix', 'string'],
            ['lazyLoadPrefix', 'default', 'value' => ''],
            [
                'autoGenerate',
                'validateAutoGenerate',
            ],
            ['autoGenerate', 'default', 'value' => false],
            ['warmCache', 'boolean'],
            ['warmCache', 'default', 'value' => false],
            ['transforms', 'array'],
            ['transforms', 'default', 'value' => []],
        ];
    }

    /**
     * Validates the autoGenerate property
     *
     * @param string $attribute
     */
    public function validateAutoGenerate(string $attribute): void
    {
        $value = $this->$attribute;
        
        // Must be either a boolean or an array
        if (!is_bool($value) && !is_array($value)) {
            $this->addError($attribute, Craft::t('imgix', 'Auto-generate must be either a boolean or an array of volume handles.'));
        }
        
        // If it's an array, all values must be strings
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!is_string($item)) {
                    $this->addError($attribute, Craft::t('imgix', 'Auto-generate array must contain only volume handle strings.'));
                    break;
                }
            }
        }
    }
}
