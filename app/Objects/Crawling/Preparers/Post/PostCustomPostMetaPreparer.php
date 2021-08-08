<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 12:22
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;


use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Utils;

class PostCustomPostMetaPreparer extends AbstractPostBotPreparer {

    /** @var array */
    private $customMeta = [];

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        $this->customMeta = [];

        // Get custom meta with selectors
        $this->prepareCustomMetaWithSelectors();

        // Get manually added custom post meta
        $this->prepareManuallyAddedCustomMeta();

        // Apply find and replace options
        $this->applyFindReplaces();

        // If there is no custom meta, stop.
        if(empty($this->customMeta)) return;

        // Store it
        $this->bot->getPostData()->setCustomMeta($this->customMeta);
    }

    /**
     * Finds the custom meta whose selectors are specified and sets them to {@link $customMeta}
     * @since 1.8.0
     */
    private function prepareCustomMetaWithSelectors() {
        $postCustomPostMetaSelectors = $this->bot->getSetting(SettingKey::POST_CUSTOM_META_SELECTORS);

        // No need to continue if there is no selector.
        if(empty($postCustomPostMetaSelectors)) return;

        foreach ($postCustomPostMetaSelectors as $selectorData) {
            // If there is no meta key, continue with the next one.
            if (!isset($selectorData["meta_key"]) || empty($selectorData["meta_key"])) continue;

            $isMultiple = isset($selectorData["multiple"]);

            // Extract the values
            $results = $this->bot->extractValuesWithSelectorData($this->getBot()->getCrawler(), $selectorData, 'text', false, !$isMultiple, true);
            if (!$results) continue;

            // Add the values
            $this->customMeta[] = [
                "data"      =>  $results,
                "meta_key"  =>  $selectorData["meta_key"],
                "multiple"  =>  $isMultiple ? 1 : 0,
            ];

        }
    }

    /**
     * Prepares the manually-entered custom meta and sets them to {@link $customMeta}
     * @since 1.8.0
     */
    private function prepareManuallyAddedCustomMeta() {
        $customPostMetaData = $this->bot->getSetting(SettingKey::POST_CUSTOM_META);

        // No need to continue if there is no custom meta.
        if(empty($customPostMetaData)) return;

        foreach($customPostMetaData as $metaData) {
            if(!isset($metaData["key"]) || !$metaData["key"] || !isset($metaData["value"])) continue;
            $isMultiple = isset($metaData["multiple"]);

            $this->customMeta[] = [
                "data"      =>  $metaData["value"],
                "meta_key"  =>  $metaData["key"],
                "multiple"  =>  $isMultiple ? 1 : 0,
            ];
        }
    }

    /**
     * Applies find and replace options for the custom meta
     * @since 1.8.0
     */
    private function applyFindReplaces() {
        $postMetaSpecificFindAndReplaces = $this->bot->getSetting(SettingKey::POST_FIND_REPLACE_CUSTOM_META);

        // If there is no custom meta or find-replace options, stop.
        if(!$this->customMeta || !$postMetaSpecificFindAndReplaces) return;

        // Find replace in specific custom meta
        // Loop over each custom meta created previously
        foreach($this->customMeta as $i => &$customMetaItem) {
            // Get current meta item's meta key and data
            $currentMetaKey = Utils::array_get($customMetaItem, "meta_key", null);
            $results        = Utils::array_get($customMetaItem, "data");

            // Continue with the next one if meta key or data does not exist in the current custom meta item.
            if(!$currentMetaKey || !$results) continue;

            // Get find-replaces for this meta key
            $currentFindReplaces = [];
            foreach($postMetaSpecificFindAndReplaces as $key => $item) {
                // If the meta key of find-replace is not the same as the current meta key, continue with the next one.
                if($item["meta_key"] != $currentMetaKey) continue;

                // Store the find-replace
                $currentFindReplaces[] = $item;

                // Remove this find-replace since this cannot be applied to another meta key. By this way, we will not
                // check this find-replace config unnecessarily for other meta keys.
                unset($postMetaSpecificFindAndReplaces[$key]);
            }

            // Apply find-replaces
            $results = $this->bot->applyFindAndReplaces($currentFindReplaces, $results);

            // If there are results, reassign it to the current custom meta item.
            $customMetaItem["data"] = $results;
        }
    }
}
