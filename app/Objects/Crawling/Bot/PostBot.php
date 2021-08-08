<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 24/08/16
 * Time: 23:50
 */

namespace WPCCrawler\Objects\Crawling\Bot;


use Exception;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Crawling\Data\PostData;
use WPCCrawler\Objects\Crawling\Preparers\BotConvenienceFindReplacePreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostCategoryPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostContentsPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostCreatedDatePreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostCustomPostMetaPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostCustomTaxonomyPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostDataPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostExcerptPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostListInfoPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostMediaPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostMetaAndTagInfoPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostPaginationInfoPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostShortCodeInfoPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostSlugPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostSpinningPreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostTemplatePreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostTitlePreparer;
use WPCCrawler\Objects\Crawling\Preparers\Post\PostTranslationPreparer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Traits\ErrorTrait;
use WPCCrawler\PostDetail\PostDetailsService;
use WPCCrawler\Utils;
use WPCCrawler\WPCCrawler;

class PostBot extends AbstractBot {

    use ErrorTrait;

    /** @var Crawler */
    private $crawler;

    /** @var PostData */
    private $postData;

    /*
     *
     */

    /** @var array */
    public $combinedListData = [];

    /** @var string */
    private $postUrl = '';

    /** @var null|Uri */
    private $postUri = null;

    /*
     *
     */

    /** @var BotConvenienceFindReplacePreparer|null */
    private $findReplacePreparer = null;

    private $keyLastEmptySelectorEmailDate = '_last_post_empty_selector_email_sent';

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * Crawls a post and prepares the data as {@link PostData}. This method does not save the post to the database.
     *
     * @param string $postUrl A full URL
     * @return PostData|null
     */
    public function crawlPost($postUrl) {
        $this->clearErrors();

        $this->setPostUrl($postUrl);
        $this->postData = new PostData();

        $findAndReplacesForRawHtml          = $this->getSetting(SettingKey::POST_FIND_REPLACE_RAW_HTML);
        $postUnnecessaryElementSelectors    = $this->getSetting(SettingKey::POST_UNNECESSARY_ELEMENT_SELECTORS);

        $this->doActionBeforeRetrieve();

        $this->crawler = $this->request($postUrl, "GET", $findAndReplacesForRawHtml);
        if(!$this->crawler) return null;

        $this
            ->doActionAfterRetrieve()
            ->applyFilterCrawlerRaw()
            ->prepareCrawler()                                       // Prepare the crawler by applying HTML manipulations and resolving relative URLs
            ->applyPreparer(PostPaginationInfoPreparer::class)  // Prepare pagination info
        ;

        // Clear the crawler from unnecessary post elements
        $this->removeElementsFromCrawler($this->crawler, $postUnnecessaryElementSelectors);

        $this->applyFilterCrawlerPrepared();

        /*
         * PREPARE
         */

        /** @noinspection PhpUnhandledExceptionInspection */
        $this
            ->applyPreparer(PostTitlePreparer::class)               // Post title
            ->applyPreparer(PostSlugPreparer::class)                // Post slug
            ->applyPreparer(PostExcerptPreparer::class)             // Post excerpt
            ->applyPreparer(PostContentsPreparer::class)            // Post contents
            ->applyPreparer(PostCategoryPreparer::class)            // Post categories
            ->applyPreparer(PostCreatedDatePreparer::class)         // Post date
            ->applyPreparer(PostShortCodeInfoPreparer::class)       // Custom short code contents
            ->applyPreparer(PostListInfoPreparer::class)            // List items
            ->applyPreparer(PostMetaAndTagInfoPreparer::class)      // Post tags and meta info
            ->applyPreparer(PostCustomPostMetaPreparer::class)      // Post meta
            ->applyPreparer(PostCustomTaxonomyPreparer::class)      // Post taxonomies
            ->applyPreparer(PostMediaPreparer::class)               // Post media. This removes gallery images from the source code.
            ->preparePostDetails()                                       // Prepare the registered post details
            ->applyPreparer(PostTemplatePreparer::class)            // Post templates. Insert main data into template
            ->applyPreparer(PostDataPreparer::class)                // Changes that should be made to all parts of the post, such as removing empty HTML tags.
            ->applyPreparer(PostTranslationPreparer::class)         // Translate
            ->applyPreparer(PostSpinningPreparer::class)            // Spin
        ;

        /* END PREPARATION */

        $this
            ->applyFilterPostData()
            ->maybeNotify()                     // Notify
            ->doActionPostDataAfterPrepared()
        ;

        return $this->postData;
    }

    /*
     * PRIVATE HELPERS
     */

    /**
     * @param string $cls Name of a class that extends {@link AbstractPostBotPreparer}.
     * @return $this
     *
     * @since 1.9.0
     * @throws Exception If $cls is not a child of {@link AbstractPostBotPreparer}.
     */
    private function applyPreparer($cls) {
        $instance = new $cls($this);
        if (!is_a($instance, AbstractPostBotPreparer::class)) {
            throw new Exception(sprintf('%1$s must be a child of %2$s', $cls, AbstractPostBotPreparer::class));
        }

        /** @var AbstractPostBotPreparer $instance */
        $instance->prepare();

        return $this;
    }

    /**
     * Prepares the registered post details
     *
     * @return $this
     * @since 1.9.0
     */
    private function preparePostDetails() {
        PostDetailsService::getInstance()->preparePostDetails($this);
        return $this;
    }

    /**
     * Prepares the crawl by applying HTML manipulations and resolving relative URLs
     *
     * @return $this
     * @since 1.9.0
     */
    private function prepareCrawler() {
        $findAndReplacesForFirstLoad = $this->getSetting(SettingKey::POST_FIND_REPLACE_FIRST_LOAD);

        // Make initial replacements
        $this->crawler = $this->makeInitialReplacements($this->crawler, $findAndReplacesForFirstLoad, true);

        // Apply HTML manipulations
        $this->applyFindAndReplaceInElementAttributes($this->crawler,   SettingKey::POST_FIND_REPLACE_ELEMENT_ATTRIBUTES);
        $this->applyExchangeElementAttributeValues($this->crawler,      SettingKey::POST_EXCHANGE_ELEMENT_ATTRIBUTES);
        $this->applyRemoveElementAttributes($this->crawler,             SettingKey::POST_REMOVE_ELEMENT_ATTRIBUTES);
        $this->applyFindAndReplaceInElementHTML($this->crawler,         SettingKey::POST_FIND_REPLACE_ELEMENT_HTML);

        // Resolve relative URLs
        $this->resolveRelativeUrls($this->crawler, $this->getPostUrl());
        return $this;
    }

    /**
     * Sets {@link $postUrl}
     *
     * @param string $postUrl
     * @since 1.8.0
     */
    private function setPostUrl($postUrl) {
        $this->postUrl = $postUrl;
        $this->postUri = null;
    }

    /**
     * Notify the user for the elements that have empty values using the settings. This will notify the user only if
     * this is not called during a test.
     *
     * @return $this
     * @since 1.9.0
     */
    private function maybeNotify() {
        // Do not notify if this is a test.
        if (WPCCrawler::isDoingTest()) return $this;

        $notifyWhenEmptySelectors = $this->getSetting(SettingKey::POST_NOTIFY_EMPTY_VALUE_SELECTORS);
        if (!$notifyWhenEmptySelectors) return $this;

        $this->notifyUser($this->postUrl, $this->crawler, $notifyWhenEmptySelectors, $this->keyLastEmptySelectorEmailDate);
        return $this;
    }

    /*
     * PUBLIC HELPERS
     */

    /**
     * Prepare find-and-replaces by adding config to the supplied find-and-replace array, such as link removal config.
     *
     * @param array $findAndReplaces An array of find and replace options. See
     *                               {@link FindAndReplaceTrait::findAndReplace} to learn more about this array.
     * @return array
     * @uses BotConvenienceFindReplacePreparer::prepare()
     */
    public function prepareFindAndReplaces($findAndReplaces) {
        // If the supplied parameter is not an array, stop and return it.
        if (!is_array($findAndReplaces)) return $findAndReplaces;

        // If the preparer does not exist, create it.
        if (!$this->findReplacePreparer) {
            $this->findReplacePreparer = new BotConvenienceFindReplacePreparer($this);
        }

        // Add the config to the given array.
        return array_merge($findAndReplaces, $this->findReplacePreparer->prepare());
    }

    /*
     * PUBLIC GETTERS AND SETTERS
     */

    /**
     * @return Crawler
     */
    public function getCrawler() {
        return $this->crawler;
    }

    /**
     * @return PostData
     */
    public function getPostData() {
        return $this->postData;
    }

    /**
     * @param PostData $postData
     */
    public function setPostData($postData) {
        $this->postData = $postData;
    }

    /**
     * Get the URL of latest crawled or being crawled post.
     *
     * @return string
     */
    public function getPostUrl() {
        return $this->postUrl;
    }

    /**
     * Resolves a URL by considering {@link $postUrl} as base URL.
     *
     * @param string $relativeUrl Relative or full URL that will be resolved against the current post URL.
     * @return string The given URL that is resolved using {@link $postUrl}
     * @see   PostBot::getPostUrl()
     * @see   Utils::resolveUrl()
     * @since 1.8.0
     * @throws Exception If post URL that will be used to resolve the given URL does not exist.
     */
    public function resolveUrl($relativeUrl) {
        if (!$this->postUrl) {
            throw new Exception("Post URL does not exist.");
        }

        // If there is no post URI, create it.
        if ($this->postUri === null) {
            $this->postUri = new Uri($this->postUrl);
        }

        return Utils::resolveUrl($this->postUri, $relativeUrl);
    }


    /*
     * ACTIONS AND FILTERS
     */

    /**
     * @return $this
     * @since 1.9.0
     */
    private function doActionBeforeRetrieve() {
        /**
         * Fires just before the source code of a post page is retrieved from the target site.
         *
         * @param int siteId        ID of the site
         * @param string $postUrl   URL of the post
         * @param PostBot $this     The bot itself
         * @since 1.6.3
         */
        do_action('wpcc/post/source-code/before_retrieve', $this->getSiteId(), $this->postUrl, $this);
        return $this;
    }

    /**
     * @return $this
     * @since 1.9.0
     */
    private function doActionAfterRetrieve() {
        /**
         * Fires just after the source code of a post page is retrieved from the target site.
         *
         * @param int siteId        ID of the site
         * @param string $postUrl   URL of the post
         * @param PostBot $this     The bot itself
         * @param Crawler $crawler  Crawler containing raw, unmanipulated source code of the target post
         * @since 1.6.3
         */
        do_action('wpcc/post/source-code/after_retrieve', $this->getSiteId(), $this->postUrl, $this, $this->crawler);
        return $this;
    }

    /**
     * @return $this
     * @since 1.9.0
     */
    private function applyFilterCrawlerRaw() {
        /**
         * Modify the raw crawler that contains source code of the target post page
         *
         * @param Crawler $crawler  Crawler containing raw, unmanipulated source code of the target post
         * @param int siteId        ID of the site
         * @param string $postUrl   URL of the post
         * @param PostBot $this     The bot itself
         *
         * @return Crawler          Modified crawler
         * @since 1.6.3
         */
        $this->crawler = apply_filters('wpcc/post/crawler/raw', $this->crawler, $this->getSiteId(), $this->postUrl, $this);
        return $this;
    }

    /**
     * @return $this
     * @since 1.9.0
     */
    private function applyFilterCrawlerPrepared() {
        /**
         * Modify the prepared crawler that contains source code of the target post page. At this point, the crawler was
         * manipulated. Unnecessary elements were removed, find-and-replace options were applied, etc.
         *
         * @param crawler Crawler   Crawler containing manipulated source code of the target post
         * @param int siteId        ID of the site
         * @param string $postUrl   URL of the post
         * @param PostBot $this     The bot itself
         *
         * @return Crawler          Modified crawler
         * @since 1.6.3
         */
        $this->crawler = apply_filters('wpcc/post/crawler/prepared', $this->crawler, $this->getSiteId(), $this->postUrl, $this);
        return $this;
    }

    /**
     * @return $this
     * @since 1.9.0
     */
    private function applyFilterPostData() {
        /**
         * Modify the prepared PostData object, which stores all the required data retrieved from the target site.
         *
         * @param PostData $postData Prepared PostData object
         * @param int      siteId    ID of the site
         * @param string   $postUrl  URL of the post
         * @param PostBot  $this     The bot itself
         * @param Crawler  $crawler  Crawler containing manipulated source code of the target post
         * @return PostData     Modified PostData
         * @since 1.6.3
         */
        $this->postData = apply_filters('wpcc/post/post-data', $this->postData, $this->getSiteId(), $this->postUrl, $this, $this->crawler);
        return $this;
    }

    /**
     * @return $this
     * @since 1.9.0
     */
    private function doActionPostDataAfterPrepared() {
        /**
         * Fires just after the post data is prepared according to the settings. All of the necessary changes were made
         * to the post data, such as removal of unnecessary elements and replacements.
         *
         * @param int      siteId    ID of the site
         * @param string   $postUrl  URL of the post
         * @param PostBot  $this     The bot itself
         * @param PostData $postData The data retrieved from the target site by using the settings configured by the user.
         * @param Crawler  $crawler  Crawler containing the target post page's source code. The crawler was manipulated
         *                           according to the settings.
         * @since 1.6.3
         */
        do_action('wpcc/post/data/after_prepared', $this->getSiteId(), $this->postUrl, $this, $this->postData, $this->crawler);
        return $this;
    }
}
