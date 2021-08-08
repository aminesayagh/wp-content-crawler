<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 07/12/2018
 * Time: 17:40
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;


use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Enums\InformationMessage;
use WPCCrawler\Objects\Enums\InformationType;
use WPCCrawler\Objects\Informing\Information;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;

class PostCustomTaxonomyPreparer extends AbstractPostBotPreparer {

    private $customTaxonomies;

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        $this->customTaxonomies = [];

        // Get custom taxonomy with selectors
        $this->prepareCustomTaxonomyWithSelectors();

        // Get manually added custom post taxonomy
        $this->prepareManuallyAddedCustomTaxonomy();

        // If there is no custom taxonomy, stop.
        if(empty($this->customTaxonomies)) return;

        // Store it
        $this->bot->getPostData()->setCustomTaxonomies($this->customTaxonomies);
    }

    /**
     * Finds the custom taxonomy whose selectors are specified and sets them to {@link $customTaxonomies}
     * @since 1.8.0
     */
    private function prepareCustomTaxonomyWithSelectors() {
        $postCustomPostTaxonomySelectors = $this->bot->getSetting(SettingKey::POST_CUSTOM_TAXONOMY_SELECTORS);

        // No need to continue if there is no selector.
        if(empty($postCustomPostTaxonomySelectors)) return;

        foreach ($postCustomPostTaxonomySelectors as $selectorData) {
            // If there is no taxonomy, continue with the next one.
            if (!isset($selectorData["taxonomy"]) || empty($selectorData["taxonomy"])) continue;

            $isMultiple = isset($selectorData["multiple"]);

            // Extract the values
            $results = $this->bot->extractValuesWithSelectorData($this->getBot()->getCrawler(), $selectorData, 'text', false, !$isMultiple, true);
            if (!$results) continue;

            // Validate the taxonomy's existence
            $taxonomyName = $selectorData["taxonomy"];
            if (!$this->validateTaxonomyExistence($taxonomyName)) continue;

            $isAppend = isset($selectorData["append"]);

            // Add the values
            $this->customTaxonomies[] = [
                "data"      =>  $results,
                "taxonomy"  =>  $taxonomyName,
                "append"    =>  $isAppend ? 1 : 0,
            ];

        }
    }

    /**
     * Prepares the manually-entered custom taxonomy and sets them to {@link $customTaxonomies}
     * @since 1.8.0
     */
    private function prepareManuallyAddedCustomTaxonomy() {
        $customPostTaxonomyData = $this->bot->getSetting(SettingKey::POST_CUSTOM_TAXONOMY);

        // No need to continue if there is no custom taxonomy.
        if(empty($customPostTaxonomyData)) return;

        foreach($customPostTaxonomyData as $taxonomyData) {
            if(!isset($taxonomyData["taxonomy"]) || !$taxonomyData["taxonomy"] || !isset($taxonomyData["value"])) continue;
            $isAppend = isset($taxonomyData["append"]);

            // Validate the taxonomy's existence
            $taxonomyName = $taxonomyData["taxonomy"];
            if (!$this->validateTaxonomyExistence($taxonomyName)) continue;

            $this->customTaxonomies[] = [
                "data"      =>  $taxonomyData["value"],
                "taxonomy"  =>  $taxonomyName,
                "append"    =>  $isAppend ? 1 : 0,
            ];
        }
    }

    /**
     * @param string $taxName Name of the taxonomy
     * @return bool True if the taxonomy is valid. Otherwise, false.
     * @since 1.8.0
     */
    private function validateTaxonomyExistence($taxName) {
        // If the taxonomy name is not valid, return false.
        if (!$taxName) return false;

        // If taxonomy does not exist, notify the user and return false.
        if (!taxonomy_exists($taxName)) {
            Informer::add(Information::fromInformationMessage(
                InformationMessage::TAXONOMY_DOES_NOT_EXIST,
                sprintf(_wpcc('Taxonomy: %1$s'), $taxName),
                InformationType::INFO
            )->addAsLog());

            return false;
        }

        // This is a valid taxonomy.
        return true;
    }

}
