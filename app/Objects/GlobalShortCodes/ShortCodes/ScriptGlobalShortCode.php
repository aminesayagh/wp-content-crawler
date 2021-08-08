<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 24/12/2018
 * Time: 10:55
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\GlobalShortCodes\ShortCodes;


use WPCCrawler\Objects\DomainValidator;
use WPCCrawler\Objects\GlobalShortCodes\ShortCodes\Base\BaseGlobalShortCode;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Utils;

class ScriptGlobalShortCode extends BaseGlobalShortCode {

    const TAG_NAME = "wpcc-script";

    /**
     * @return string Tag of the short code. E.g. "wpcc-iframe". Use only lower case characters.
     * @since 1.8.0
     */
    public function getTagName() {
        return static::TAG_NAME;
    }

    /**
     * @param array       $attributes The attributes passed to the short code. These are prepared by allowing only the
     *                                keys defined in {@link getDefaults()}.
     * @param string|null $content    Content of the short code, if exists.
     * @return string Output of the short code.
     * @since 1.8.0
     */
    protected function parse($attributes, $content) {
        // TODO: This is too similar to IFrameGlobalShortCode::parse. Make this DRY.
        $src = Utils::array_get($attributes, "src");
        if (!$src) return '';

        // Check the validity of the source. If it is not from a valid domain, return an empty string.
        $isValid = DomainValidator::getInstance()->validate(SettingKey::WPCC_ALLOWED_SCRIPT_SHORT_CODE_DOMAINS, $src);
        if (!$isValid) return '';

        $attrString = $this->combineAttributesAsHtmlAttributeString($attributes);

        // Create and output the script element
        return "<script {$attrString}></script>";
    }

    /**
     * @return array|null A key-value pair where keys are the allowed attributes of the short code, and the values are
     *                    their default values. This array will be used to sanitize the attributes of the short code.
     *                    If this returns null, no sanitizing will be done and all attributes will be available.
     * @since 1.8.0
     */
    protected function getDefaults() {
        return null;
    }
}
