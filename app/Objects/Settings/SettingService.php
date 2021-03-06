<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 24/08/16
 * Time: 10:16
 */

namespace WPCCrawler\Objects\Settings;


use WPCCrawler\Environment;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Enums\PostStatus;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Transformation\Spinning\SpinningService;
use WPCCrawler\Objects\Transformation\Translation\TranslationService;
use WPCCrawler\Utils;

class SettingService {

    private static $ALL_GENERAL_SETTINGS = null;

    /**
     * Get all settings related to the general settings page
     * @return array General settings for the content crawler
     */
    public static function getAllGeneralSettings() {
        if(static::$ALL_GENERAL_SETTINGS) return static::$ALL_GENERAL_SETTINGS;
        global $wpdb;

        // Get all options related to the content crawler
        $options = $wpdb->get_results("
            SELECT option_name, option_value
            FROM $wpdb->options
            WHERE option_name LIKE '_wpcc_%'
        ");

        // When the options are saved by update_option function, some characters are escaped by slashes. Since we
        // get all values directly with a MySQL query (without an unescape operation), we need to unescape them.
        $optionsPrepared = [];
        foreach ((array)$options as $o) {
            $optionsPrepared[$o->option_name] =
                is_serialized($o->option_value) ?
                    Utils::arrayStripSlashes(unserialize($o->option_value)) :
                    (is_array($o->option_value) ?
                        Utils::arrayStripSlashes($o->option_value) :
                        stripslashes($o->option_value)
                    );
        }

        static::$ALL_GENERAL_SETTINGS = $optionsPrepared;

        return static::$ALL_GENERAL_SETTINGS;
    }

    /**
     * Check if the scheduling is currently active
     * @return bool
     */
    public static function isSchedulingActive() {
        return get_option(SettingKey::WPCC_IS_SCHEDULING_ACTIVE) ? true : false;
    }

    /**
     * Check if recrawling is currently active
     * @return bool
     */
    public static function isRecrawlingActive() {
        return get_option(SettingKey::WPCC_IS_RECRAWLING_ACTIVE) ? true : false;
    }

    /**
     * Check if deleting posts is currently active
     * @return bool
     */
    public static function isDeletingActive() {
        return get_option(SettingKey::WPCC_IS_DELETING_POSTS_ACTIVE) ? true : false;
    }

    /**
     * Check if post translation is currently active
     * @return bool
     */
    public static function isTranslationActive() {
        return get_option(SettingKey::WPCC_IS_TRANSLATION_ACTIVE) ? true : false;
    }

    /**
     * Check if post spinning is currently active
     * @return bool
     */
    public static function isSpinningActive() {
        return get_option(SettingKey::WPCC_IS_SPINNING_ACTIVE) ? true : false;
    }

    /**
     * Check if Tooltip used in the UI should be disabled
     * @return bool
     */
    public static function isTooltipDisabled() {
        return get_option(SettingKey::WPCC_DISABLE_TOOLTIP) ? true : false;
    }

    /**
     * Check if the notifications are currently active
     * @return bool
     */
    public static function isNotificationActive() {
        return get_option(SettingKey::WPCC_IS_NOTIFICATION_ACTIVE) ? true : false;
    }

    /**
     * Get email addresses to which notifications can be sent
     *
     * @return array
     */
    public static function getNotificationEmails() {
        return array_filter(array_unique(get_option(SettingKey::WPCC_NOTIFICATION_EMAILS, [])));
    }

    /**
     * Get email notification interval in minutes. This is number of minutes that should pass before sending another
     * similar notification about the same site.
     *
     * @return int
     */
    public static function getEmailNotificationInterval() {
        $key = SettingKey::WPCC_NOTIFICATION_EMAIL_INTERVAL_FOR_SITE;
        $val = get_option($key, Factory::generalSettingsController()->getDefaultGeneralSettings()[$key]);
        return (int) $val;
    }

    /**
     * Get the variables necessary for general settings page. Variables are
     *      'settings' (all options for general settings),
     *      'postStatuses' (available post statuses to select),
     *      'authors' (available authors to select as post author),
     *      'intervals' ()
     * @param bool $isGeneralPage Set false when getting settings for a site's settings page. By this way, you can get
     *      only necessary variables.
     * @return array
     */
    public static function getSettingsPageVariables($isGeneralPage = true) {
        $result = [];

        if($isGeneralPage) {
            $allSettings = static::getAllGeneralSettings();

            $settings = $allSettings;
            // If a setting's value is array, then, to comply with post meta traditions (since form items are designed
            // for post meta), make the value an array and add a new entry to the array, which is the value's serialized form.
            // So, by this way, form items will work as expected.
            foreach($allSettings as $key => $mSetting) {
                if(is_array($mSetting)) {
                    $serialized = serialize($mSetting);
                    $mSetting = [];
                    $mSetting[] = $serialized;
                    $settings[$key] = $mSetting;
                }
            }

            $result['settings'] = $settings;
        }

        // Post statuses
        $postStatuses = [
            PostStatus::PUBLISH => _wpcc('Publish'),
            PostStatus::DRAFT   => _wpcc('Draft'),
            PostStatus::PENDING => _wpcc('Pending'),
            PostStatus::PRIVATE => _wpcc('Private')
        ];

        $result['postStatuses'] = $postStatuses;

        // Get authors
        $authorsRaw = get_users([
            'orderby'   =>  'nicename',
            'fields'    =>  ['ID', 'user_nicename']
        ]);

        $authors = [];
        foreach($authorsRaw as $author) {
            $authors[$author->ID] = $author->user_nicename;
        }

        $result['authors'] = $authors;

        // CRON intervals
        if($isGeneralPage) {
            $intervals = [];
            foreach (Factory::schedulingService()->getIntervals() as $key => $interval) {
                $intervals[$key] = $interval[0];
            }
            $result['intervals'] = $intervals;
        }

        $postTypes = get_post_types();
        if(isset($postTypes[Environment::postType()])) unset($postTypes[Environment::postType()]);
        $result["postTypes"] = $postTypes;

        // Translation
        $result["translationApiClients"] = TranslationService::getInstance()->getOptionsForSelect();
        $result["translationLanguages"]  = TranslationService::getInstance()->getLanguagesForView();

        $result["spinningApiClients"]    = SpinningService::getInstance()->getOptionsForSelect();

        return $result;
    }

    /**
     * Handles some AJAX requests sent from settings pages (i.e. general settings and site settings)
     *
     * @param array $data
     * @return null|string Null if the request type is not defined. Otherwise, JSON.
     */
    public static function respondToAjaxRequest($data) {
        $requestType = Utils::array_get($data, "requestType");
        $isOption    = Utils::array_get($data, "isOption");

        switch($requestType) {
            case "load_refresh_translation_languages":
                return TranslationService::handleLoadRefreshTranslationLanguagesRequest($data, $isOption);

            case "clear_translation_languages":
                return TranslationService::handleClearLanguagesRequest($data, $isOption);
        }

        return null;
    }

}
