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
     * Imgix signed URLs token (deprecated - use signingToken in imgixDomains array)
     *
     * @var string
     * @deprecated Use signingToken in imgixDomains array configuration
     */
    public $imgixSignedToken = '';

    /**
     * @var string
     */
    public $lazyLoadPrefix = '';

    /**
     * Prevent upscaling images beyond their original size
     *
     * @var bool
     */
    public $preventUpscaling = false;

    public function getApiKey()
    {
        $apiKey = Craft::parseEnv($this->apiKey);

        if (!empty($apiKey) && strlen($apiKey) < 50) {
            \Craft::$app->deprecator->log(__METHOD__, 'You appear to be using an deprecated API key for the Imgix API. You need to generate a new one from https://dashboard.imgix.com/api-keys/new, with permissions to purge, and replace the old one. See https://blog.imgix.com/2020/10/16/api-deprecation for more information.');
        }

        return $apiKey;
    }

    /**
     * Get domain configuration for a volume handle
     * Supports both legacy string format and new array format
     * 
     * @param string $volumeHandle Volume handle to lookup
     * @return array|null Array with 'domain', 'signingToken', and 'path' keys, or null if not found
     *                    Note: 'path' is returned without leading/trailing slashes
     */
    public function getDomainConfig(string $volumeHandle): ?array
    {
        if (!isset($this->imgixDomains[$volumeHandle])) {
            return null;
        }

        $config = $this->imgixDomains[$volumeHandle];

        // Handle new array format
        if (is_array($config)) {
            $domain = $config['domain'] ?? null;
            $signingToken = isset($config['signingToken']) ? Craft::parseEnv($config['signingToken']) : null;
            $path = $config['path'] ?? '';

            if ($domain === null) {
                return null;
            }

            // Normalize path - trim leading/trailing slashes
            $path = trim($path, '/');

            return [
                'domain' => $domain,
                'signingToken' => $signingToken,
                'path' => $path,
            ];
        }

        // Handle legacy string format (domain or domain/path)
        $domainParts = explode('/', $config, 2);
        $domain = $domainParts[0];
        $path = count($domainParts) === 2 ? trim($domainParts[1], '/') : '';

        // Use deprecated imgixSignedToken as fallback for legacy format
        $signingToken = null;
        if (!empty($this->imgixSignedToken)) {
            $signingToken = Craft::parseEnv($this->imgixSignedToken);
        }

        return [
            'domain' => $domain,
            'signingToken' => $signingToken,
            'path' => $path,
        ];
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
            ['preventUpscaling', 'boolean'],
            ['preventUpscaling', 'default', 'value' => false],
        ];
    }
}
