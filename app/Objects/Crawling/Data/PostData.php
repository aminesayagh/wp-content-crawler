<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 25/08/16
 * Time: 11:33
 */

namespace WPCCrawler\Objects\Crawling\Data;


use WPCCrawler\Objects\File\MediaFile;
use WPCCrawler\Objects\Transformation\Interfaces\Transformable;
use WPCCrawler\Utils;

class PostData implements Transformable {

    const FIELD_TITLE                   = 'title';
    const FIELD_EXCERPT                 = 'excerpt.data';
    const FIELD_CATEGORY_NAMES          = 'categoryNames';
    const FIELD_NEXT_PAGE_URL           = 'nextPageUrl';
    const FIELD_ALL_PAGE_URLS           = 'allPageUrls.data';
    const FIELD_SLUG                    = 'slug';
    const FIELD_TEMPLATE                = 'template';
    const FIELD_SHORT_CODE_DATA         = 'shortCodeData.data';
    const FIELD_PREPARED_TAGS           = 'preparedTags';
    const FIELD_META_KEYWORDS           = 'metaKeywords';
    const FIELD_META_DESCRIPTION        = 'metaDescription';
    const FIELD_CUSTOM_META             = 'customMeta.data';
    const FIELD_ATTACHMENT_TITLE        = 'attachmentData.mediaTitle';
    const FIELD_ATTACHMENT_DESCRIPTION  = 'attachmentData.mediaDescription';
    const FIELD_ATTACHMENT_CAPTION      = 'attachmentData.mediaCaption';
    const FIELD_ATTACHMENT_ALT          = 'attachmentData.mediaAlt';
    const FIELD_THUMBNAIL_TITLE         = 'thumbnailData.mediaTitle';
    const FIELD_THUMBNAIL_DESCRIPTION   = 'thumbnailData.mediaDescription';
    const FIELD_THUMBNAIL_CAPTION       = 'thumbnailData.mediaCaption';
    const FIELD_THUMBNAIL_ALT           = 'thumbnailData.mediaAlt';
    const FIELD_CUSTOM_TAXONOMIES       = 'customTaxonomies.data';

    /**
     * @var null|array An array of names of the post categories. Each item is a string or array. If the item is a
     *                 string, then it is one of the main categories of the post. If it is an array, it represents
     *                 a category hierarchy. Each previous category name in the array is the parent category name of the
     *                 item. E.g. ['cat1', 'cat2', 'cat3'] represents 'cat1 > cat2 > cat3' hierarchy.
     */
    private $categoryNames;

    /** @var bool */
    private $paginate;

    /** @var string */
    private $nextPageUrl;

    /** @var array */
    private $allPageUrls;

    /*
     *
     */

    /** @var string */
    private $title;

    /** @var array */
    private $excerpt;

    /** @var array */
    private $contents;

    /** @var string */
    private $dateCreated;

    /** @var array */
    private $shortCodeData;

    /** @var array */
    private $tags;

    /** @var array */
    private $preparedTags;

    /** @var string */
    private $slug;

    /*
     * LIST
     */

    /** @var int */
    private $listStartPos;

    /** @var array */
    private $listNumbers;

    /** @var array */
    private $listTitles;

    /** @var array */
    private $listContents;

    /*
     * META
     */

    /** @var string */
    private $metaKeywords;

    /** @var array */
    private $metaKeywordsAsTags;

    /** @var string */
    private $metaDescription;

    /*
     *
     */

    /** @var null|MediaFile */
    private $thumbnailData;

    /** @var MediaFile[] */
    private $attachmentData = [];

    /*
     *
     */

    /** @var array */
    private $customMeta;

    /** @var array */
    private $customTaxonomies;

    /** @var string */
    private $template;

    /*
     *
     */

    /** @var array WordPress post data */
    private $wpPostData = [];

    /*
     * GETTERS AND SETTERS
     */

    /**
     * @return array|null See {@link $categoryNames}
     */
    public function getCategoryNames() {
        return $this->categoryNames;
    }

    /**
     * @param array|null $categoryNames See {@link $categoryNames}
     */
    public function setCategoryNames($categoryNames) {
        $this->categoryNames = $categoryNames;
    }

    /**
     * @return boolean
     */
    public function isPaginate() {
        return $this->paginate;
    }

    /**
     * @param boolean $paginate
     */
    public function setPaginate($paginate) {
        $this->paginate = $paginate;
    }

    /**
     * @return string
     */
    public function getNextPageUrl() {
        return $this->nextPageUrl;
    }

    /**
     * @param string $nextPageUrl
     */
    public function setNextPageUrl($nextPageUrl) {
        $this->nextPageUrl = $nextPageUrl;
    }

    /**
     * @return array
     */
    public function getAllPageUrls() {
        return $this->allPageUrls;
    }

    /**
     * @param array $allPageUrls
     */
    public function setAllPageUrls($allPageUrls) {
        $this->allPageUrls = $allPageUrls;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getExcerpt() {
        return $this->excerpt;
    }

    /**
     * @param array $excerpt See {@link excerpt}
     */
    public function setExcerpt($excerpt) {
        $this->excerpt = $excerpt;
    }

    /**
     * @return array
     */
    public function getContents() {
        return $this->contents;
    }

    /**
     * @param array $contents
     */
    public function setContents($contents) {
        $this->contents = $contents;
    }

    /**
     * @return string
     */
    public function getDateCreated() {
        return $this->dateCreated;
    }

    /**
     * @param string $dateCreated
     */
    public function setDateCreated($dateCreated) {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @return array
     */
    public function getShortCodeData() {
        return $this->shortCodeData;
    }

    /**
     * @param array $shortCodeData
     */
    public function setShortCodeData($shortCodeData) {
        $this->shortCodeData = $shortCodeData;
    }

    /**
     * @return array
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }

    /**
     * @return array
     */
    public function getPreparedTags() {
        return $this->preparedTags;
    }

    /**
     * @param array $preparedTags
     */
    public function setPreparedTags($preparedTags) {
        $this->preparedTags = $preparedTags;
    }

    /**
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug) {
        $this->slug = $slug;
    }

    /**
     * @return int
     */
    public function getListStartPos() {
        return $this->listStartPos;
    }

    /**
     * @param int $listStartPos
     */
    public function setListStartPos($listStartPos) {
        $this->listStartPos = $listStartPos;
    }

    /**
     * @return array
     */
    public function getListNumbers() {
        return $this->listNumbers;
    }

    /**
     * @param array $listNumbers
     */
    public function setListNumbers($listNumbers) {
        $this->listNumbers = $listNumbers;
    }

    /**
     * @return array
     */
    public function getListTitles() {
        return $this->listTitles;
    }

    /**
     * @param array $listTitles
     */
    public function setListTitles($listTitles) {
        $this->listTitles = $listTitles;
    }

    /**
     * @return array
     */
    public function getListContents() {
        return $this->listContents;
    }

    /**
     * @param array $listContents
     */
    public function setListContents($listContents) {
        $this->listContents = $listContents;
    }

    /**
     * @return string
     */
    public function getMetaKeywords() {
        return $this->metaKeywords;
    }

    /**
     * @param string $metaKeywords
     */
    public function setMetaKeywords($metaKeywords) {
        $this->metaKeywords = $metaKeywords;
    }

    /**
     * @return array
     */
    public function getMetaKeywordsAsTags() {
        return $this->metaKeywordsAsTags;
    }

    /**
     * @param array $metaKeywordsAsTags
     */
    public function setMetaKeywordsAsTags($metaKeywordsAsTags) {
        $this->metaKeywordsAsTags = $metaKeywordsAsTags;
    }

    /**
     * @return string
     */
    public function getMetaDescription() {
        return $this->metaDescription;
    }

    /**
     * @param string $metaDescription
     */
    public function setMetaDescription($metaDescription) {
        $this->metaDescription = $metaDescription;
    }

    /**
     * @return MediaFile|null
     */
    public function getThumbnailData() {
        return $this->thumbnailData;
    }

    /**
     * @param MediaFile $mediaFile
     */
    public function setThumbnailData($mediaFile) {
        $this->thumbnailData = $mediaFile;
    }

    /**
     * @return MediaFile[]
     */
    public function getAttachmentData() {
        return $this->attachmentData;
    }

    /**
     * @param MediaFile[] $attachmentData
     */
    public function setAttachmentData($attachmentData) {
        $this->attachmentData = $attachmentData ?: [];
    }

    /**
     * Deletes previously saved attachments.
     */
    public function deleteAttachments() {
        if(!$this->getAttachmentData()) return;

        foreach($this->getAttachmentData() as $mediaFile) {
            Utils::deleteFile($mediaFile->getLocalPath());

            // If the media file has an ID, delete the attachment with that ID.
            if ($mediaFile->getMediaId()) {
                wp_delete_attachment($mediaFile->getMediaId(), true);
            }
        }
    }

    /**
     * @return array
     */
    public function getCustomMeta() {
        return $this->customMeta;
    }

    /**
     * @param array $customMeta See {@link customMeta}
     */
    public function setCustomMeta($customMeta) {
        $this->customMeta = $customMeta;
    }

    /**
     * @return array
     */
    public function getCustomTaxonomies() {
        return $this->customTaxonomies;
    }

    /**
     * @param array $customTaxonomies
     */
    public function setCustomTaxonomies($customTaxonomies) {
        $this->customTaxonomies = $customTaxonomies;
    }

    /**
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function getWpPostData() {
        return $this->wpPostData;
    }

    /**
     * @param array $wpPostData
     */
    public function setWpPostData($wpPostData) {
        $this->wpPostData = $wpPostData;
    }

    /**
     * Get all media files, which contain attachment media files and the thumbnail media file.
     *
     * @return MediaFile[]
     * @since 1.8.0
     */
    public function getAllMediaFiles() {
        $mediaFiles = $this->getAttachmentData();
        if ($this->getThumbnailData()) $mediaFiles[] = $this->getThumbnailData();
        return $mediaFiles;
    }

    public function getTransformableFields() {
        return [
            static::FIELD_TITLE                  => _wpcc('Title'),
            static::FIELD_EXCERPT                => _wpcc('Excerpt'),
            static::FIELD_CATEGORY_NAMES         => _wpcc('Category Names'),
            static::FIELD_SLUG                   => _wpcc('Slug'),
            static::FIELD_TEMPLATE               => _wpcc('Content'),
            static::FIELD_PREPARED_TAGS          => _wpcc('Tags'),
            static::FIELD_META_KEYWORDS          => _wpcc('Meta Keywords'),
            static::FIELD_META_DESCRIPTION       => _wpcc('Meta Description'),
            static::FIELD_CUSTOM_META            => _wpcc('Custom Meta'),
            static::FIELD_ATTACHMENT_TITLE       => _wpcc('Media Title'),
            static::FIELD_ATTACHMENT_DESCRIPTION => _wpcc('Media Description'),
            static::FIELD_ATTACHMENT_CAPTION     => _wpcc('Media Caption'),
            static::FIELD_ATTACHMENT_ALT         => _wpcc('Media Alternate Text'),
            static::FIELD_THUMBNAIL_TITLE        => _wpcc('Thumbnail Title'),
            static::FIELD_THUMBNAIL_DESCRIPTION  => _wpcc('Thumbnail Description'),
            static::FIELD_THUMBNAIL_CAPTION      => _wpcc('Thumbnail Caption'),
            static::FIELD_THUMBNAIL_ALT          => _wpcc('Thumbnail Alternate Text'),
            static::FIELD_CUSTOM_TAXONOMIES      => _wpcc('Taxonomies'),
        ];
    }

    public function getInteractableFields() {
        return $this->getTransformableFields() + [
            static::FIELD_NEXT_PAGE_URL   => _wpcc('Next Page URL'),
            static::FIELD_ALL_PAGE_URLS   => _wpcc('All Page URLs'),
            static::FIELD_SHORT_CODE_DATA => _wpcc('Custom Short Codes'),
        ];
    }
}