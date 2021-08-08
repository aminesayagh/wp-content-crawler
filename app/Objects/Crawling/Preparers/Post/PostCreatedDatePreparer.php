<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 11:36
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;


use DateTime;
use WPCCrawler\Environment;
use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;

class PostCreatedDatePreparer extends AbstractPostBotPreparer {

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        $minutesToAdd = $this->bot->getSetting(SettingKey::POST_DATE_ADD_MINUTES);

        // Create the post date
        $finalDate = $this->getDateWithSelectors() ?: current_time('mysql');

        // Create a DateTime object for the date so that we can manipulate it as we please.
        $dt = new DateTime($finalDate);

        // Now, manipulate the date if the user defined how many minutes should be added to the date.
        if($minutesToAdd) {
            // Minutes can be comma-separated. Get each minute by making sure they are integers.
            $minutes = array_map(function ($m) {
                return (int) trim($m);
            }, explode(",", $minutesToAdd));

            // If there are minutes, get a random one and add it to the date.
            if($minutes) {
                $dt->modify($minutes[array_rand($minutes)] . " minute");
            }
        }

        // Set the date in postData after formatting it by MySQL date format
        $this->bot->getPostData()->setDateCreated($dt->format(Environment::mysqlDateFormat()));
    }

    /**
     * @return null|string If a date is found, it will be returned. Otherwise, null.
     * @since 1.9.0
     */
    private function getDateWithSelectors() {
        $dates = $this->getValuesForSelectorSetting(SettingKey::POST_DATE_SELECTORS, 'content', false, false, true);
        if (!$dates) return null;

        $findAndReplacesForDate = $this->bot->getSetting(SettingKey::POST_FIND_REPLACE_DATE);

        // Since we get multiple values when extracting the data, the resultant array is a multidimensional array.
        // To be able to use the data easily, we flatten the array.
        $dates = array_flatten($dates);
        foreach($dates as $date) {
            // Apply find-and-replaces
            $date = $this->bot->findAndReplace($findAndReplacesForDate, $date);

            // Get the timestamp. If there is a valid timestamp, prepare the date and assign it
            // to postData
            if($timestamp = strtotime($date)) {
                // Get the date in MySQL date format.
                // No need to continue. One match is enough.
                $finalDate = date(Environment::mysqlDateFormat(), $timestamp);
                return $finalDate !== false ? $finalDate : null;

            } else {
                // Notify the user.
                Informer::addInfo(sprintf(_wpcc('Date "%1$s" could not be parsed.'), $date))
                    ->addAsLog();
            }
        }

        return null;
    }
}
