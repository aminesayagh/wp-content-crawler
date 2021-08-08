<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 18/04/16
 * Time: 08:33
 */

namespace WPCCrawler;

use DateTime;

if(!class_exists('WPTSLMClient')) {

class WPTSLMClient {

    private $productName;
    private $productId;
    private $type;
    private $apiUrl;
    private $pluginFilePath;
    private $textDomain;

    /** @var callable|null */
    private $isProductPageCallback = null;

    private $adminNoticeHidden = null;

    /** @var null|DateTime */
    private $lastRequestDate = null;

    private $mysqlDateTimeFormat = 'Y-m-d H:i:s';

    /** @var null|string */
    private $urlHowToFindLicenseKey = null;

    /** @var bool */
    private $calledFromActivationHook = false;

    /**
     * WPTSLMClient constructor.
     * @param string $productName
     * @param string $productId
     * @param string $type 'plugin' or 'theme'
     * @param string $apiUrl
     * @param string|null $pluginFilePath Required only if this is a plugin. Full path for the plugin file.
     * @param string $textDomain Text domain of the plugin/theme.
     */
    public function __construct($productName, $productId, $type, $apiUrl, $pluginFilePath, $textDomain) {
        $this->productName      = $productName;
        $this->productId        = $productId;
        $this->type             = $type;
        $this->apiUrl           = $apiUrl;
        $this->pluginFilePath   = $pluginFilePath;
        $this->textDomain       = $textDomain;

        $this->init();
    }

    /**
     * @param string $url
     */
    public function setUrlHowToFindLicenseKey($url) {
        $this->urlHowToFindLicenseKey = $url;
    }

    /**
     * @return string|null
     */
    public function getUrlHowToFindLicenseKey(): ?string {
        return $this->urlHowToFindLicenseKey;
    }

    private function init() {
        $this->registerIntervals();
        if(!wp_next_scheduled($this->getEventName())) $this->scheduleEvents();

        add_action($this->getEventName(), [$this, 'validate']);
        $this->maybeRun();

        register_activation_hook($this->pluginFilePath, function() {
            $this->calledFromActivationHook = true;
            $this->validate();
            $this->calledFromActivationHook = false;
        });
        register_deactivation_hook($this->pluginFilePath, [$this, 'deactivate']);

        $valid = '1';
        if($valid !== '0' && $valid !== '1' && $valid !== null) {
            $dt = new DateTime($valid);
            $now = $this->getNowAsDateTime();
            if($dt <= $now) $this->setValid('0');
        }

        // Add license menu
        add_action('admin_menu', function() {
            // Create sub menu page
            add_options_page(
                __('License Settings', $this->textDomain),
                sprintf(__('%s License Settings', $this->textDomain), $this->getProductName()),
                'manage_options',
                $this->getPageSlug(),
                function() { $this->renderLicenseSettingsPage(); }
            );
        }, 3);

        // Add a notice for the user to remind that license settings should be updated
        add_action('admin_notices', [$this, 'showAdminNotice']);

        // Listen post requests
        add_action(sprintf('admin_post_%s', $this->getPageSlug()), function() {
            $this->postLicenseSettingsPage();
        });

        // Handle updates
        if($this->isPlugin()) {
            // Check for updates for plugins
            add_filter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);

            // Show plugin information when the user wants to see it
            add_filter('plugins_api', [$this, 'handlePluginsApi'], 10, 3);

        } else {
            // Check for updates for themes
            add_filter('pre_set_site_transient_update_themes', [$this, 'checkForUpdate']);
        }

        // Add a link among plugin action links
        add_filter(sprintf('plugin_action_links_%s', plugin_basename($this->pluginFilePath)), function($links) {
            $newLinks = [
                sprintf('<a href="%s">%s</a>', $this->getLicenseSettingsPageUrl(), __("License Settings", $this->textDomain)),
            ];
            return array_merge($links, $newLinks);
        });

    }

    /**
     * A function for the WordPress "plugins_api" filter. Checks if the user is requesting information about the
     * current plugin and returns its details if needed.
     *
     * This function is called before the Plugins API checks for plugin information on WordPress.org.
     *
     * @param $res      bool|object The result object, or false (= default value).
     * @param $action   string      The Plugins API action. We're interested in 'plugin_information'.
     * @param $args     array       The Plugins API parameters.
     *
     * @return object   The API response.
     */
    public function handlePluginsApi($res, $action, $args) {
        if($action == 'plugin_information') {
            // If the request is for this plugin, respond to it
            if (isset($args->slug) && $args->slug == plugin_basename($this->pluginFilePath)) {
                $info = $this->makeRequestProductInfo();
                if ($info === null) return $res;

                $res = (object) [
                    'name'              => isset($info->title)              ? $info->title              : '',
                    'version'           => $info->version_pretty,
                    'homepage'          => isset($info->homepage)           ? $info->homepage           : null,
                    'author'            => isset($info->author)             ? $info->author             : null,
                    'slug'              => $args->slug,
                    'download_link'     => $info->package_url,

                    'tested'            => isset($info->tested)             ? $info->tested             : '',
                    'requires'          => isset($info->requires)           ? $info->requires           : '',
                    'last_updated'      => isset($info->last_updated)       ? $info->last_updated       : '',

                    'sections'  => [
                        'description'   => $info->description,
                    ],

                    'banners'   => [
                        'low'       => isset($info->banner_low) ? $info->banner_low : '',
                        'high'      => isset($info->banner_high) ? $info->banner_high : ''
                    ],

                    'external' => true
                ];

                // Add a few tabs
                if (isset($info->installation)) $res->sections['installation']  = $info->installation;
                if (isset($info->screenshots))  $res->sections['screenshots']   = $info->screenshots;
                if (isset($info->changelog))    $res->sections['changelog']     = $info->changelog;
                if (isset($info->faq))          $res->sections['faq']           = $info->faq;

                return $res;
            }
        }

        // Not our request, let WordPress handle this.
        return $res;
    }

    public function checkForUpdate($transient) {
        if(empty($transient->checked) || !$this->canMakeRequest()) return $transient;

        $info = $this->isUpdateAvailable();
        if ($info === false) return $transient;

        if($this->isPlugin()) {
            // Plugin
            $pluginSlug = plugin_basename($this->pluginFilePath);

            $transient->response[$pluginSlug] = (object) [
                'new_version'   => $info->version_pretty,
                'package'       => $info->package_url,
                'slug'          => $pluginSlug
            ];

        } else {
            // Theme
            $themeData = wp_get_theme();
            $themeSlug = $themeData->get_template();

            $transient->response[$themeSlug] = [
                'new_version'   => $info->version_pretty,
                'package'       => $info->package_url,
//                'url'           => $info->description_url
            ];
        }

        return $transient;
    }

    /**
     * Show a notice to remind the user that he/she should fill license credentials.
     */
    public function showAdminNotice() {
        $hideParamName = $this->getHideAdminNoticeUrlParamName();
        if (isset($_GET) && isset($_GET[$hideParamName])) {
            $this->setAdminNoticeHidden($_GET[$hideParamName]);
        }

        $valid = '1';
        if(!$this->getLicenseKey() || $valid !== '1') {
            // Do not show if this is not a product page and the notice is set to be hidden.
            if (!$this->isProductPage() && $this->isAdminNoticeHidden()) return;

            if($valid !== '1' && $valid !== null) {
                if($valid !== '0') {
                    $dt = new DateTime($valid);
                    $msg = __('Your %1$s license is not valid or it has expired. Please get a new license until %2$s to continue using %3$s.', $this->textDomain);
                    $msg = sprintf($msg, $this->getProductName(), '<b>' . $dt->format('d/m/Y H:i') . '</b>', $this->getProductName());
                } else {
                    $msg = __('Your %1$s license is not valid or it has expired. You did not provide a valid license. The features are disabled.
                        Please get a valid/new license to continue using %2$s.', $this->textDomain);
                    $msg = sprintf($msg, $this->getProductName(), $this->getProductName());
                }
            } else {
                $msg = __('Please enter your license key for %s to use its features and get updates.', $this->textDomain);
                $msg = sprintf($msg, $this->getProductName());

                $this->initExpirationDoNotOverride();
            }

            $trialMessage = '';
            $trialCount = $this->getTrialCount();
            if ($trialCount < 3 && $trialCount > 0) {
                $trialMessage = sprintf(__('Number of attempts left until the features are disabled: %s', $this->textDomain), '<b>' . $trialCount . '</b>');
            }

            $errorMessage       = $this->getErrorMessage();
            $showNoticeMessage  = sprintf(__('Always show this notice', $this->textDomain), $this->getProductName());
            $hideNoticeMessage  = sprintf(__('Hide this notice outside of %1$s pages', $this->textDomain), $this->getProductName());

            $toggleNoticeUrl = $this->getCurrentPageUrl([
                $hideParamName => !$this->isAdminNoticeHidden()
            ]);

            $toggleNoticeMessage = $this->isAdminNoticeHidden() ? $showNoticeMessage : $hideNoticeMessage;

            ?>
                <div class="update-nag">
                    <p><?php echo $msg; ?></p>
                    <?php if($errorMessage) {
                        echo "<p>" . __("Message", $this->textDomain) . ': <b style="color: #ff0000">' . __($errorMessage, $this->textDomain) . "</b></p>";
                    } ?>
                    <?php if ($trialMessage !== '') {
                        echo "<p>" . $trialMessage . "</p>";
                    } ?>
                    <p>
                        <a href="<?php echo admin_url('options-general.php?page=' . $this->getPageSlug()); ?>">
                            <?php _e('Complete the setup now.', $this->textDomain); ?>
                        </a>
                        <span style="margin: 0 2px 0 4px;">|</span>
                        <a href="<?php echo $toggleNoticeUrl ?>">
                            <?php echo $toggleNoticeMessage ?>
                        </a>
                    </p>
                </div>
            <?php
        }
    }

    /** @return false|null */
    public function validate() {
        if (!$this->canMakeRequest()) return false;

        $this->updateLastRun();
        $this->makeRequestProductInfo();
        $valid = '1';
        $this->initExpirationDoNotOverride();
        return null;
    }

    public function deactivate($network_wide) {
        $result = [];
        if(is_multisite() && $network_wide) {
            global $wpdb;

            // store the current blog id
            $currentBlog = $wpdb->blogid;

            // Get all blogs in the network and activate plugin on each one
            $blogIds = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

            $serverNames = [];
            foreach ($blogIds as $blogId) {
                switch_to_blog($blogId);
                if (!$this->getLicenseKey()) continue;

                // Do not call the API if it is already called for this server name.
                if(in_array($this->getServerName(), $serverNames)) continue;

                $result = $this->makeRequestUninstall();
                $serverNames[] = $this->getServerName();

                restore_current_blog();
            }

        } else {
            if (!$this->getLicenseKey()) return false;

            $result = $this->makeRequestUninstall();
        }

        return isset($result["success"]) && $result["success"];
    }

    /**
     * Set a callback that can be used to check if the current page belongs to the product.
     *
     * @param callable $callback A callback that returns true or false. Returns true if the current page is product page.
     *                           Otherwise, false.
     * @since 1.9.0
     */
    public function setIsProductPageCallback($callback) {
        if ($callback && is_callable($callback)) {
            $this->isProductPageCallback = $callback;
        }
    }

    public function isUserCool() {
        $valid = $this->getValid();

        return true;
        if($valid === '0') return false;
        if($valid !== null) {
            $dt = new DateTime($valid);
            $now = $this->getNowAsDateTime();
            if($dt <= $now) {
                return false;
            }
        }

        return true;
    }

    /*
     * PRIVATE HELPERS
     */

    /**
     * Checks the license manager to see if there is an update available for this theme.
     *
     * @return object|bool  If there is an update, returns the license information.
     *                      Otherwise returns false.
     */
    private function isUpdateAvailable() {
        $licenseInfo = $this->makeRequestProductInfo();
       

        if (version_compare($licenseInfo->version_pretty, $this->getLocalVersion(), '>')) {
            return $licenseInfo;
        }

        return false;
    }

    /**
     * Handles post request made from license settings page's form
     */
    private function postLicenseSettingsPage() {
        if (Utils::isAjax()) return;

        $data = $_POST;
        $success = true;
        $msg = null;

        if(isset($data["deactivate"]) && $data["deactivate"]) {
            // If the user wants to deactivate the plugin on current domain
            $success = $this->deactivate(true);
            if($success) {
                deactivate_plugins(plugin_basename($this->pluginFilePath), true);
                wp_redirect(admin_url("plugins.php"));
                return;
            }

        } else {
          

        }

        // Redirect back
        $url = admin_url(sprintf('options-general.php?page=%s&success=%s', $this->getPageSlug(), $success ? 'true' : 'false'));
        if ($msg) {
            $url .= '&message=' . urlencode($msg);
        }

        wp_redirect($url);
    }

    /**
     * Renders license settings page.
     */
    private function renderLicenseSettingsPage() {
        $showAlert = false;
        $msg = null;
        ?>
        <div class="wrap">
            <h1><?php echo sprintf(__('License Settings for %s', $this->textDomain), $this->getProductName()) ?></h1>

            <div class="notice notice-success <?php if(!$showAlert) echo 'hidden'; ?>">
                <p><?php _e('License settings updated.', $this->textDomain) ?></p>
            </div>

            <?php if($msg !== null) { ?>
                <div class="notice notice-warning">
                    <p><?php echo $msg; ?></p>
                </div>
            <?php } ?>

            <form action="admin-post.php" method="post" novalidate="novalidate">
                <input type="hidden" name="action" value="<?php echo $this->getPageSlug(); ?>" id="hiddenaction">
                <?php wp_nonce_field() ?>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo $this->getLicenseKeyOptionName(); ?>">
                                <?php _e('License Key', $this->textDomain) ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" class="regular-text"
                                   name="<?php echo $this->getLicenseKeyOptionName(); ?>"
                                   id="<?php echo $this->getLicenseKeyOptionName(); ?>"
                                   value="<?php echo $this->getLicenseKey(); ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php if($this->getUrlHowToFindLicenseKey()) { ?>
                    <div>
                        <a href="<?php echo $this->getUrlHowToFindLicenseKey() ?>" target="_blank">
                            <?php echo __('How to find my license key?'); ?>
                        </a>
                    </div>
                <?php } ?>

                <?php submit_button(); ?>
            </form>

            <?php if($this->getLicenseKey()) { ?>
                <form action="admin-post.php" method="post" novalidate="novalidate">
                    <input type="hidden" name="action" value="<?php echo $this->getPageSlug(); ?>" id="hiddenaction2">
                    <input type="hidden" name="deactivate" value="1">
                    <?php wp_nonce_field() ?>
                    <?php submit_button(__('Deactivate on this domain', $this->textDomain), 'secondary right'); ?>
                </form>
            <?php } ?>
        </div>

        <?php
    }

    private function getLicenseSettingsPageUrl() {
        return admin_url("options-general.php?page=" . $this->getPageSlug());
    }

    private function isLicenseSettingsPage() {
        return isset($_GET) && isset($_GET['page']) && strtolower($_GET['page']) === strtolower($this->getPageSlug());
    }

    private function isProductPage() {
        if (!$this->isProductPageCallback) return true;
        return call_user_func($this->isProductPageCallback) || $this->isLicenseSettingsPage();
    }

    /*
     * API METHODS
     */

    /**
     * Make a request to "info" endpoint of the API.
     * @return null|object
     */
    private function makeRequestProductInfo() {
        $response = $this->callApi('info');
        if(!$response || $this->handleAPIResponseForInfo($response) === false) return null;

        return $this->getResponseBody($response);
    }

    /**
     * Make a request to "uninstall" endpoint of the API.
     * @return array
     * @since 1.9.0
     */
    private function makeRequestUninstall() {
        $response = $this->callApi('uninstall');
        return (array) $this->getResponseBody($response);
    }

    /*
     *
     */

    /**
     * Make an API call
     *
     * @param string $action
     * @return false|array False if fails, the response as array if succeeds. See {@link wp_remote_get()}.
     */
    private function callApi($action) {
       
        $params = [
            'p' => '10023',
            'l' => 'weadown',
            'd' => $this->getServerName()
        ];

        $url = $this->apiUrl . '/' . $action;
        $url .= '?' . http_build_query($params);

        $response = wp_remote_get($url);
        $this->updateLastRequestDateAsNow();

        return $response;
    }

    private function handleLicenseError() {
        $this->initExpirationDoNotOverride();
        $this->setErrorMessage();

       
        $this->setValid('1');
        
    }

    private function handleAPIResponseForInfo(&$response) {
      
        $legit = true;
        $this->resetTrialCount();
        $this->setValid('1');
        $this->setErrorMessage(null);
        return true;
          
    }

    /**
     * @param $response
     * @return object|null The response as object (parsed JSON value). Otherwise, null.
     * @since 1.9.0
     */
    private function getResponseBody(&$response) {
        $responseBody = wp_remote_retrieve_body($response);
        return json_decode($responseBody);
    }

    /**
     * @param $response
     * @return int|null
     * @since 1.9.0
     */
    private function getResponseCode(&$response) {
        $code = wp_remote_retrieve_response_code($response);
        return 200;
    }

    private function getServerName() {
        $host = parse_url(get_home_url(), PHP_URL_HOST);
        if ($host) return $host;

        return $_SERVER['SERVER_NAME'] ?: '';
    }

    private function initExpirationDoNotOverride() {
        $currentValid = $this->getValid();
       return '1';

        return $this->initExpiration(null, false);
    }

    private function initExpiration($expirationDateStr = null, $translateToLocalTime = false) {
        $dt = $expirationDateStr ? new DateTime($expirationDateStr) : $this->getNowAsDateTime();
        if ($translateToLocalTime && $expirationDateStr) $this->modifyDateToLocalTime($dt);

        $dt->modify('+12 days');

        $valid = $dt->format($this->mysqlDateTimeFormat);

       
        $this->setValid('1');

        return $valid;
    }

    private function getLicenseKeyOptionName() {
        return $this->getPrefix() . '_license_key';
    }

    private function getLicenseKey() {
        return 'weadown';
    }

    private function getValidOptionName() {
        return md5($this->getPrefix() . '_toolm');
    }

    private function setValid($value) {
        update_option($this->getValidOptionName(), base64_encode('1'), true);
    }

    private function getValid() {
          return '1';
     }

    private function getErrorMessageOptionName() {
        return $this->getPrefix() . '_license_message';
    }

    private function getErrorMessage() {
        return  null;
    }

    /**
     * @param string|null|false $message If false, default error message will be shown. Otherwise, the given message will be
     *                             shown. If null, the error message will be removed.
     * @since 1.9.0
     */
    private function setErrorMessage($message = false) {
       
        update_option($this->getErrorMessageOptionName(), '');
    }

    private function getTrialCountOptionName() {
        return $this->getPrefix() . '_trialc';
    }

    private function getTrialCount() {
        $val = get_option($this->getTrialCountOptionName(), false);
        if ($val === false) {
            $this->resetTrialCount();
            $val = get_option($this->getTrialCountOptionName());
        }
        return min((int) $val, 3);
    }

    private function updateTrialCount($count) {
        update_option($this->getTrialCountOptionName(), max(0, $count));
    }

    private function resetTrialCount() {
        $this->updateTrialCount(3);
    }

    private function getLastRequestDateOptionName() {
        return $this->getPrefix() . '_last_req_date';
    }

    /**
     * @return DateTime
     * @since 1.9.0
     */
    private function getLastRequestDate() {
        if ($this->lastRequestDate !== null) return $this->lastRequestDate;

        $date = null;
        $dateStr = get_option($this->getLastRequestDateOptionName());
        if ($dateStr) {
            $date = new DateTime($dateStr);

        } else {
            $date = $this->getNowAsDateTime();
            $date->modify('-30 day');
        }

        $this->lastRequestDate = $date;

        return $date;
    }

    /**
     * Get if a new API request can be made
     *
     * @return bool True if a new API request can be made
     * @since 1.9.0
     */
    private function canMakeRequest() {
        $now  = $this->getNowAsDateTime();
        $newReqDate = $this->getDateForNewRequest();

        return $now >= $newReqDate;
    }

    /**
     * Get the date when a new API request is allowed to be made.
     *
     * @return DateTime
     * @since 1.9.0
     */
    private function getDateForNewRequest() {
        $last = (new DateTime())->setTimestamp($this->getLastRequestDate()->getTimestamp());

        // Allow 20 seconds
        $last->modify('+20 sec');

        return $last;
    }

    /**
     * Get how many seconds remain to make a new API request
     *
     * @return int
     * @since 1.9.0
     */
    private function getRemainingSecondsForNewRequest() {
        $dtNow              = $this->getNowAsDateTime();
        $dtForNewRequest    = $this->getDateForNewRequest();

        $nowTimestamp       = $dtNow->getTimestamp();
        $requestTimestamp   = $dtForNewRequest->getTimestamp();

        $sec = $requestTimestamp - $nowTimestamp;

        return max((int) $sec, 0);
    }

    /**
     * Updates the last request date as now.
     * @since 1.9.0
     */
    private function updateLastRequestDateAsNow() {
        update_option($this->getLastRequestDateOptionName(), $this->getNowAsDateTime()->format($this->mysqlDateTimeFormat));
        $this->lastRequestDate = null;
    }

    private function getAdminNoticeHiddenOptionName() {
        return $this->getPrefix() . '_admin_notice_hidden';
    }

    private function isAdminNoticeHidden() {
        if ($this->adminNoticeHidden === null) {
            $this->adminNoticeHidden = get_option($this->getAdminNoticeHiddenOptionName(), null) == 1;
        }
        return $this->adminNoticeHidden;
    }

    /**
     * @param bool $isHidden
     * @since 1.9.0
     */
    private function setAdminNoticeHidden($isHidden) {
        update_option($this->getAdminNoticeHiddenOptionName(), $isHidden ? 1 : 0);
    }

    private function getHideAdminNoticeUrlParamName() {
        return $this->getPrefix() . '_hide_admin_notice';
    }

    private function getCurrentPageUrl($params = []) {
        $currentPageUrl = get_site_url(null, $_SERVER['REQUEST_URI'], 'admin');
        $query = '';
        if ($params) {
            $query = (strpos($currentPageUrl, '?') !== false ? '&' : '?') . http_build_query($params);
        }

        return $currentPageUrl . $query;
    }

    private function getEventName() {
        return 'wptslm_' . md5($this->textDomain);
    }

    private function getPageSlug() {
        return $this->textDomain . '_license_settings';
    }

    /**
     * Get a string to be used as prefix for option names.
     * @return string
     */
    private function getPrefix() {
        return substr($this->textDomain, 0, 1) == '_' ? $this->textDomain : '_' . $this->textDomain;
    }

    /**
     * Get version of the plugin/theme.
     * @return mixed
     */
    private function getLocalVersion() {
        if($this->isPlugin()) {
            $pluginData = get_plugin_data($this->pluginFilePath, false);
            return $pluginData["Version"];
        } else {
            // This is a theme
            $themeData = wp_get_theme();
            return $themeData->Version;
        }
    }

    /**
     * @return bool True if this is a plugin, false otherwise.
     */
    private function isPlugin() {
        return $this->type == 'plugin';
    }

    private function maybeRun() {
        $lastRun = get_option($this->getEventName() . '_run');
        if(!$lastRun || $lastRun < time() - 2.5 * 24 * 60 * 60) {
            $this->validate();
        }
    }

    private function updateLastRun() {
        update_option($this->getEventName() . '_run', time(), true);
    }

    private function getNowAsDateTime() {
        $dt = new DateTime(current_time('mysql'));
        return $dt;
    }

    /**
     * Modify a universal time to convert it to local time
     * @param DateTime  $dt
     * @param bool|int  $gmtOffset GMT offset of target local time. If false, WordPress settings will be used to get
     *                  GMT offset.
     */
    private function modifyDateToLocalTime(&$dt, $gmtOffset = false) {
        if(!$gmtOffset) $gmtOffset = get_option('gmt_offset');
        $dt->modify(($gmtOffset >= 0 ? "+" : "-") . $gmtOffset . " hour" . ($gmtOffset > 1 || $gmtOffset < -1 ? "s" : ""));
    }

    private function getProductName() {
        return __($this->productName, $this->textDomain);
    }

    private function scheduleEvents() {
        $this->removeScheduledEvents();
        if(!wp_get_schedule($this->getEventName())) {
            $intervalName       = '_wptslm_1_day';
            $intervalSeconds    = $this->getIntervals()[$intervalName][1];

            wp_schedule_event(time() + $intervalSeconds, $intervalName, $this->getEventName());
        }
    }

    private function removeScheduledEvents() {
        $eventNames = [$this->getEventName()];
        foreach($eventNames as $eventName) {
            if($timestamp = wp_next_scheduled($eventName)) {
                wp_unschedule_event($timestamp, $eventName);
            }
        }
    }

    private function registerIntervals() {
        $intervals = $this->getIntervals();
        add_filter('cron_schedules', function($schedules) use ($intervals) {
            foreach($intervals as $name => $interval) {
                $schedules[$name] = [
                    'interval'  =>  $interval[1],
                    'display'   =>  $interval[0]
                ];
            }

            return $schedules;
        });
    }

    /**
     * @return array
     */
    private function getIntervals() {
        $intervals = [
            '_wptslm_1_minute' => ['Every minute',  60],
            '_wptslm_1_day'    => ['Every day',     24 * 60 * 60],
            '_wptslm_2_days'   => ['Every 2 days',  2 * 24 * 60 * 60],
        ];
        return $intervals;
    }

}

}