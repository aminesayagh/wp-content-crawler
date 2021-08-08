<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 24/11/2018
 * Time: 09:05
 */

namespace WPCCrawler\PostDetail;


use WPCCrawler\Objects\Crawling\Data\PostData;
use WPCCrawler\Objects\Crawling\Savers\PostSaver;

class PostSaverData {

    /** @var PostSaver The post saver that created this. */
    private $postSaver;

    /** @var int ID of the target WordPress post */
    private $postId;

    /** @var PostData PostData instance for which WooCommerce product details will be saved. */
    private $postData;

    /** @var boolean True if this a recrawl operation. Otherwise, false. */
    private $isRecrawl;

    /** @var boolean True if saving should be done for the first page of the post. */
    private $isFirstPage;

    /** @var object URL tuple that is used to save the current post */
    private $urlTuple;

    /** @var array Attachment IDs that should be set as product gallery images */
    private $galleryAttachmentIds;

    /**
     * DetailSaverData constructor.
     *
     * @param PostSaver $postSaver            See {@link $postSaver}
     * @param int       $postId               See {@link $postId}
     * @param PostData  $postData             See {@link $postData}
     * @param bool      $isRecrawl            See {@link $isRecrawl}
     * @param bool      $isFirstPage          See {@link $isFirstPage}
     * @param string    $urlTuple             See {@link $urlTuple}
     * @param array     $galleryAttachmentIds See {@link $galleryAttachmentIds}
     */
    public function __construct(PostSaver $postSaver, $postId, PostData $postData, $isRecrawl, $isFirstPage,
                                $urlTuple, $galleryAttachmentIds = []) {

        $this->postSaver            = $postSaver;
        $this->postId               = $postId;
        $this->postData             = $postData;
        $this->isFirstPage          = $isFirstPage;
        $this->isRecrawl            = $isRecrawl;
        $this->urlTuple             = $urlTuple;
        $this->galleryAttachmentIds = $galleryAttachmentIds;
    }

    /**
     * @return PostSaver
     */
    public function getPostSaver() {
        return $this->postSaver;
    }

    /**
     * @return int
     */
    public function getPostId() {
        return $this->postId;
    }

    /**
     * @return PostData
     */
    public function getPostData() {
        return $this->postData;
    }

    /**
     * @return bool
     */
    public function isRecrawl() {
        return $this->isRecrawl;
    }

    /**
     * @return bool
     */
    public function isFirstPage() {
        return $this->isFirstPage;
    }

    /**
     * @return object
     */
    public function getUrlTuple() {
        return $this->urlTuple;
    }

    /**
     * @return null|string URL of the post
     */
    public function getUrl() {
        return isset($this->urlTuple->url) ? $this->urlTuple->url : null;
    }

    /**
     * @return array
     */
    public function getWpPostData() {
        return $this->getPostData()->getWpPostData();
    }

    /**
     * @return array
     */
    public function getGalleryAttachmentIds() {
        return $this->galleryAttachmentIds;
    }
}