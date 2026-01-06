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
     * Imgix signed URLs token (legacy single token)
     *
     * @var string
     */
    public $imgixSignedToken = '';

    /**
     * Volume handles mapped to Imgix signed tokens
     *
     * @var array
     */
    public $imgixSignedTokens = [];

    /**
     * @var string
     */
    public $lazyLoadPrefix = '';

    public function getApiKey()
    {
        $apiKey = Craft::parseEnv($this->apiKey);

        if (!empty($apiKey) && strlen($apiKey) < 50) {
            \Craft::$app->deprecator->log(__METHOD__, 'You appear to be using an deprecated API key for the Imgix API. You need to generate a new one from https://dashboard.imgix.com/api-keys/new, with permissions to purge, and replace the old one. See https://blog.imgix.com/2020/10/16/api-deprecation for more information.');
        }

        return $apiKey;
    }

    /**
     * Get the signing token for a specific volume handle or domain
     * 
     * @param string|null $volumeHandleOrDomain Volume handle or domain to lookup token for
     * @return string|null The signing token or null if not found
     */
    public function getSigningToken(?string $volumeHandleOrDomain = null): ?string
    {
        // If a specific volume/domain is provided, try to find it in the new mapping
        if ($volumeHandleOrDomain && !empty($this->imgixSignedTokens)) {
            // First try direct lookup by volume handle
            if (isset($this->imgixSignedTokens[$volumeHandleOrDomain])) {
                return Craft::parseEnv($this->imgixSignedTokens[$volumeHandleOrDomain]);
            }
            
            // Try to extract just the domain name (without path) and look it up
            $domainParts = explode('/', $volumeHandleOrDomain, 2);
            $domain = $domainParts[0];
            if (isset($this->imgixSignedTokens[$domain])) {
                return Craft::parseEnv($this->imgixSignedTokens[$domain]);
            }
        }
        
        // Fall back to the legacy single token if set
        if (!empty($this->imgixSignedToken)) {
            return Craft::parseEnv($this->imgixSignedToken);
        }
        
        return null;
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
            ['imgixSignedTokens', 'array'],
            ['imgixSignedTokens', 'default', 'value' => []],
            ['lazyLoadPrefix', 'string'],
            ['lazyLoadPrefix', 'default', 'value' => ''],
        ];
    }
}
