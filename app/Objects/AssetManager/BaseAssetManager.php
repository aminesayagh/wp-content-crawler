<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/12/2018
 * Time: 12:41
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\AssetManager;

use WPCCrawler\Environment;

/**
 * Class BaseAssetManager
 *
 * @package WPCCrawler\objects\assetManager
 * @since   1.8.0
 */
abstract class BaseAssetManager {

    /** @var bool True if script localization was done at least once. If not, false. */
    private $isLocalized = false;

    /** @var BaseAssetManager[] */
    protected static $instances = [];

    /**
     * Get the instance.
     *
     * @return BaseAssetManager
     * @since 1.8.0
     */
    public static function getInstance() {
        $calledClass = get_called_class();
        if (!isset(static::$instances[$calledClass])) {
            static::$instances[$calledClass] = new $calledClass();
        }
        return static::$instances[$calledClass];
    }

    /** This is a singleton */
    protected function __construct() {}
    final protected function __clone() {}

    /**
     * Register and enqueue a style.
     *
     * @param string      $handle Handle of the style
     * @param string      $src    Source path of the style file relative to WordPress root directory
     * @param array       $deps   An array of dependent styles
     * @param bool|string $ver    Version of the file
     * @param string      $media  See {@link wp_register_style()}
     * @see wp_register_style()
     * @see wp_enqueue_style()
     * @see BaseAssetManager::getSourceUrl()
     */
    protected function addStyle($handle, $src = null, $deps = [], $ver = false, $media = 'all') {
        $src = str_replace(DIRECTORY_SEPARATOR, "/", $src);

        // Register it only if it was not registered
        if(!wp_style_is($handle, 'registered')) {
            if(!$src) return;
            $src = $this->getSourceUrl($src);

            if(!$ver) $ver = $this->getLastModifiedTime($src);
            wp_register_style($handle, $src, $deps, $ver, $media);
        }

        // Add it only if it was not enqueued
        if(!wp_style_is($handle, 'enqueued')) {
            wp_enqueue_style($handle);
        }
    }

    /**
     * Register, enqueue and/or localize a script. Localization values will only be added once.
     *
     * @param string      $handle Handle of the script
     * @param string      $src    Source path of the script file relative to WordPress root directory
     * @param array       $deps   An array of dependent styles
     * @param bool|string $ver    Version of the file
     * @param bool $in_footer
     * @see wp_register_script()
     * @see wp_enqueue_script()
     * @see BaseAssetManager::getSourceUrl()
     */
    protected function addScript($handle, $src = null, $deps = [], $ver = false, $in_footer = false) {
        $src = str_replace(DIRECTORY_SEPARATOR, "/", $src);

        // Register it only if it was not registered
        if(!wp_script_is($handle, 'registered')) {
            if(!$src) return;
            $src = $this->getSourceUrl($src);

            if(!$ver) $ver = $this->getLastModifiedTime($src);
            wp_register_script($handle, $src, $deps, $ver, $in_footer);
        }

        // Add it only if it was not enqueued
        if(!wp_script_is($handle, 'enqueued')) {
            wp_enqueue_script($handle);

            // Add script localization if it was not added before. It is enough to do this once. No need to print the
            // same values to the page's source code for each script. Once is enough.
            if(!$this->isLocalized) {
                if ($this->getLocalizationName() && $this->getLocalizationValues()) {
                    wp_localize_script($handle, $this->getLocalizationName(), $this->getLocalizationValues());
                }

                $this->isLocalized = true;
            }
        }
    }

    /**
     * Get last modified time of an asset.
     *
     * @param string $relativePath Path relative to the WP installation directory
     * @return false|int False if the file is not found, last modified time otherwise.
     */
    protected function getLastModifiedTime($relativePath) {
        $fullPath = str_replace("/", DIRECTORY_SEPARATOR, trailingslashit(ABSPATH) . ltrim($relativePath, DIRECTORY_SEPARATOR));
        if(file_exists($fullPath)) {
            return filemtime($fullPath);
        }

        return false;
    }

    /**
     * Returns the localizations for the scripts. For localization values to be added, a valid localization value must
     * be returned from {@link getLocalizationValues()}.
     *
     * @return string|null A string that will be the variable name of the JavaScript localization values. E.g. if this
     *                     is 'wpcc', localization values defined in {@link getLocalizationValues()} will be available
     *                     under 'wpcc' variable in the JS window. So, to define localization values, override
     *                     {@link getLocalizationValues()}.
     * @since 1.8.0
     * @see   wp_localize_script()
     * @see   BaseAssetManager::getLocalizationValues()
     */
    protected function getLocalizationName() {
        return null;
    }

    /**
     * Get script localization values. For localization values to be added, a valid localization name must be returned
     * from {@link getLocalizationName()}.
     *
     * @return array A key-value pair, where keys are the array keys of localization variable in JS, and the values are
     *               their values. E.g. ['error_occurred' => 'An error occurred'].
     * @see wp_localize_script()
     * @see BaseAssetManager::getLocalizationName()
     */
    protected function getLocalizationValues() {
        return [];
    }

    /*
     *
     */

    /**
     * Get the source URL. This method decides what asset should be used. For example, if the development assets are
     * wanted, it changes the given source URL with its development version, if it exists.
     *
     * If the given source URL has a version whose name ends with "-dev", that version will be returned. For example,
     * if source URL is "/wp-content/plugins/wp-content-crawler/app/public/dist/dev-tools.js", this method will look
     * for "/wp-content/plugins/wp-content-crawler/app/public/dist/dev-tools-dev.js" when the debug mode is enabled. The
     * debug mode is enabled if $_GET has "debug". If "-dev" version of the file is available, this method returns
     * that version's URL.
     *
     * @param string $src Relative source URL of the asset
     * @return string|mixed If given URL is not valid, returns the given URL. Otherwise, URL of the asset.
     * @since 1.8.0
     */
    private function getSourceUrl($src) {
        // If there is no source, return the given source URL.
        if (!$src) return $src;

        // Check if the dev version is required
        $isDev = isset($_GET["debug"]) || Environment::isDev();
        if (!$isDev) return $src;

        // If ABSPATH does not exist, we cannot find the development version of the file. So, stop.
        if (!defined('ABSPATH')) return $src;

        // Create the development source URL
        $devSrc = preg_replace('/(\.[^.]*)$/', '-dev$1', $src, 1);
        if ($devSrc === $src) return $src;

        // Get the path of the source. Make sure there are no multiple forward slashes and the directory separator of
        // the OS is used instead of forward slashes.
        $devSrcPath = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            preg_replace('/\/{2,}/', '/', ABSPATH . DIRECTORY_SEPARATOR . $devSrc)
        );

        // If the development version of the file does not exist, return the given source URL.
        if(!file_exists($devSrcPath)) return $src;

        // Return the development version
        return $devSrc;
    }
}