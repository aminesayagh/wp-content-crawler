<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 16/11/2018
 * Time: 18:57
 */

namespace WPCCrawler\PostDetail\WooCommerce;


use WPCCrawler\Objects\File\MediaFile;
use WPCCrawler\Objects\Transformation\Interfaces\Transformable;
use WPCCrawler\PostDetail\Base\BasePostDetailData;
use WPCCrawler\PostDetail\WooCommerce\Data\ProductAttribute;

class WooCommerceData extends BasePostDetailData implements Transformable {

    // TODO: Save reviews

    const FIELD_PRODUCT_URL                 = 'productUrl';
    const FIELD_BUTTON_TEXT                 = 'buttonText';
    const FIELD_REGULAR_PRICE               = 'regularPrice';
    const FIELD_SALE_PRICE                  = 'salePrice';
    const FIELD_DOWNLOADABLE_TITLE          = 'downloadableMediaFiles.mediaTitle';
    const FIELD_DOWNLOADABLE_DESCRIPTION    = 'downloadableMediaFiles.mediaDescription';
    const FIELD_DOWNLOADABLE_CAPTION        = 'downloadableMediaFiles.mediaCaption';
    const FIELD_DOWNLOADABLE_ALT            = 'downloadableMediaFiles.mediaAlt';
    const FIELD_SKU                         = 'sku';
    const FIELD_STOCK_QUANTITY              = 'stockQuantity';
    const FIELD_WEIGHT                      = 'weight';
    const FIELD_LENGTH                      = 'length';
    const FIELD_WIDTH                       = 'width';
    const FIELD_HEIGHT                      = 'height';
    const FIELD_PURCHASE_NOTE               = 'purchaseNote';
    const FIELD_ATTRIBUTE_KEY               = 'attributes.key';
    const FIELD_ATTRIBUTE_VALUE             = 'attributes.values';

    /** @var string */
    private $productType;

    /** @var bool */
    private $isVirtual;

    /** @var bool */
    private $isDownloadable;

    /** @var string */
    private $productUrl;

    /** @var string */
    private $buttonText;

    /** @var double */
    private $regularPrice;

    /** @var double */
    private $salePrice;

    /** @var MediaFile[] */
    private $downloadableMediaFiles = [];

    /** @var int */
    private $downloadLimit;

    /** @var int */
    private $downloadExpiry;

    /** @var string */
    private $sku;

    /** @var bool */
    private $isManageStock;

    /** @var int */
    private $stockQuantity;

    /** @var string */
    private $backorders;

    /** @var int */
    private $lowStockAmount;

    /** @var string */
    private $stockStatus;

    /** @var bool */
    private $isSoldIndividually;

    /** @var double */
    private $weight;

    /** @var double */
    private $length;

    /** @var double */
    private $width;

    /** @var double */
    private $height;

    /** @var int */
    private $shippingClassId;

    /** @var string */
    private $purchaseNote;

    /** @var bool */
    private $enableReviews;

    /** @var int */
    private $menuOrder;

    /** @var null|array */
    private $galleryImageUrls;

    /** @var null|ProductAttribute[] */
    private $attributes;

    /**
     * @return string|null
     */
    public function getProductType() {
        return $this->productType;
    }

    /**
     * @param string $productType
     */
    public function setProductType($productType) {
        $this->productType = $productType;
    }

    /**
     * @return bool|null
     */
    public function isVirtual() {
        return $this->isVirtual;
    }

    /**
     * @param bool $isVirtual
     */
    public function setIsVirtual($isVirtual) {
        $this->isVirtual = $isVirtual;
    }

    /**
     * @return bool|null
     */
    public function isDownloadable() {
        return $this->isDownloadable;
    }

    /**
     * @param bool $isDownloadable
     */
    public function setIsDownloadable($isDownloadable) {
        $this->isDownloadable = $isDownloadable;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return string|null
     */
    public function getProductUrl($default = null) {
        return $this->productUrl !== null ? $this->productUrl : $default;
    }

    /**
     * @param string $productUrl
     */
    public function setProductUrl($productUrl) {
        $this->productUrl = $productUrl;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return string|null
     */
    public function getButtonText($default = null) {
        return $this->buttonText !== null ? $this->buttonText : $default;
    }

    /**
     * @param string $buttonText
     */
    public function setButtonText($buttonText) {
        $this->buttonText = $buttonText;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return float|null
     */
    public function getRegularPrice($default = null) {
        return $this->regularPrice !== null ? $this->regularPrice : $default;
    }

    /**
     * @param float $regularPrice
     */
    public function setRegularPrice($regularPrice) {
        $this->regularPrice = $regularPrice;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return float|null
     */
    public function getSalePrice($default = null) {
        return $this->salePrice !== null ? $this->salePrice : $default;
    }

    /**
     * @param float $salePrice
     */
    public function setSalePrice($salePrice) {
        $this->salePrice = $salePrice;
    }

    /**
     * @return MediaFile[]|null
     */
    public function getDownloadableMediaFiles() {
        return $this->downloadableMediaFiles;
    }

    /**
     * @param MediaFile[] $downloadableMediaFiles
     */
    public function setDownloadableMediaFiles($downloadableMediaFiles) {
        $this->downloadableMediaFiles = $downloadableMediaFiles;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return int|null
     */
    public function getDownloadLimit($default = null) {
        return $this->downloadLimit !== null ? $this->downloadLimit : $default;
    }

    /**
     * @param int $downloadLimit
     */
    public function setDownloadLimit($downloadLimit) {
        $this->downloadLimit = $downloadLimit;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return int|null
     */
    public function getDownloadExpiry($default = null) {
        return $this->downloadExpiry !== null ? $this->downloadExpiry : $default;
    }

    /**
     * @param int $downloadExpiry
     */
    public function setDownloadExpiry($downloadExpiry) {
        $this->downloadExpiry = $downloadExpiry;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return string|null
     */
    public function getSku($default = null) {
        return $this->sku !== null ? $this->sku : $default;
    }

    /**
     * @param string $sku
     */
    public function setSku($sku) {
        $this->sku = $sku;
    }

    /**
     * @return bool|null
     */
    public function isManageStock() {
        return $this->isManageStock;
    }

    /**
     * @param bool $isManageStock
     */
    public function setIsManageStock($isManageStock) {
        $this->isManageStock = $isManageStock;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return int|null
     */
    public function getStockQuantity($default = null) {
        return $this->stockQuantity !== null ? $this->stockQuantity : $default;
    }

    /**
     * @param int $stockQuantity
     */
    public function setStockQuantity($stockQuantity) {
        $this->stockQuantity = $stockQuantity;
    }

    /**
     * @return string|null
     */
    public function getBackorders() {
        return $this->backorders;
    }

    /**
     * @param string $backorders
     */
    public function setBackorders($backorders) {
        $this->backorders = $backorders;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return int|null
     */
    public function getLowStockAmount($default = null) {
        return $this->lowStockAmount !== null ? $this->lowStockAmount : $default;
    }

    /**
     * @param int $lowStockAmount
     */
    public function setLowStockAmount($lowStockAmount) {
        $this->lowStockAmount = $lowStockAmount;
    }

    /**
     * @return string|null
     */
    public function getStockStatus() {
        return $this->stockStatus;
    }

    /**
     * @param string $stockStatus
     */
    public function setStockStatus($stockStatus) {
        $this->stockStatus = $stockStatus;
    }

    /**
     * @return bool|null
     */
    public function isSoldIndividually() {
        return $this->isSoldIndividually;
    }

    /**
     * @param bool $isSoldIndividually
     */
    public function setIsSoldIndividually($isSoldIndividually) {
        $this->isSoldIndividually = $isSoldIndividually;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return float|null
     */
    public function getWeight($default = null) {
        return $this->weight !== null ? $this->weight : $default;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight) {
        $this->weight = $weight;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return float|null
     */
    public function getLength($default = null) {
        return $this->length !== null ? $this->length : $default;
    }

    /**
     * @param float $length
     */
    public function setLength($length) {
        $this->length = $length;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return float|null
     */
    public function getWidth($default = null) {
        return $this->width !== null ? $this->width : $default;
    }

    /**
     * @param float $width
     */
    public function setWidth($width) {
        $this->width = $width;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return float|null
     */
    public function getHeight($default = null) {
        return $this->height !== null ? $this->height : $default;
    }

    /**
     * @param float $height
     */
    public function setHeight($height) {
        $this->height = $height;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return int|null
     */
    public function getShippingClassId($default = null) {
        return $this->shippingClassId !== null ? $this->shippingClassId : $default;
    }

    /**
     * @param int $shippingClassId
     */
    public function setShippingClassId($shippingClassId) {
        $this->shippingClassId = $shippingClassId;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return string
     */
    public function getPurchaseNote($default = null) {
        return $this->purchaseNote !== null ? $this->purchaseNote : $default;
    }

    /**
     * @param string $purchaseNote
     */
    public function setPurchaseNote($purchaseNote) {
        $this->purchaseNote = $purchaseNote;
    }

    /**
     * @return bool
     */
    public function isEnableReviews() {
        return (bool) $this->enableReviews;
    }

    /**
     * @param bool $enableReviews
     */
    public function setEnableReviews($enableReviews) {
        $this->enableReviews = $enableReviews;
    }

    /**
     * @param mixed $default Default value to be returned if the value is null.
     * @return int
     */
    public function getMenuOrder($default = null) {
        return $this->menuOrder !== null ? $this->menuOrder : $default;
    }

    /**
     * @param int $menuOrder
     */
    public function setMenuOrder($menuOrder) {
        $this->menuOrder = $menuOrder;
    }

    /**
     * @return array|null
     */
    public function getGalleryImageUrls() {
        return $this->galleryImageUrls;
    }

    /**
     * @param array|null $galleryImageUrls
     */
    public function setGalleryImageUrls($galleryImageUrls) {
        $this->galleryImageUrls = $galleryImageUrls;
    }

    /**
     * @return ProductAttribute[]|null
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @param ProductAttribute[]|null $attributes
     */
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }

    /**
     * Validates all of the data defined in this class. Removes/changes invalid ones.
     */
    public function validateData() {
        // Downloads
        $this->setDownloadLimit($this->getDownloadLimit(''));
        $this->setDownloadExpiry($this->getDownloadExpiry(''));

        // Sku
        $this->setSku($this->getSku(''));

        // Stocks
        $this->setLowStockAmount($this->getLowStockAmount(''));
        $this->setStockQuantity($this->getStockQuantity(''));

        // Weight and dimensions
        $this->setWeight($this->getWeight(''));
        $this->setLength($this->getLength(''));
        $this->setWidth($this->getWidth(''));
        $this->setHeight($this->getHeight(''));

        // Shipping class ID
        $this->setShippingClassId($this->getShippingClassId(0));

        // Purchase note
        $this->setPurchaseNote($this->getPurchaseNote(''));

        // When adding new values, make sure the values are valid and in a format that WooCommerce wants. For example,
        // prices might not be float after applying short codes. Likewise, other integer or float values might not be
        // valid. Decimal separators might not be valid, which might result in the prices not being saved, etc. Check
        // everything and validate all data.
    }

    public function getTransformableFields() {
        return [
            static::FIELD_BUTTON_TEXT              => _wpcc('Product Button Text'),
            static::FIELD_DOWNLOADABLE_TITLE       => _wpcc('Product Media Title'),
            static::FIELD_DOWNLOADABLE_DESCRIPTION => _wpcc('Product Media Description'),
            static::FIELD_DOWNLOADABLE_CAPTION     => _wpcc('Product Media Caption'),
            static::FIELD_DOWNLOADABLE_ALT         => _wpcc('Product Media Alternate Text'),
            static::FIELD_PURCHASE_NOTE            => _wpcc('Product Purchase Note'),
            static::FIELD_ATTRIBUTE_KEY            => _wpcc('Product Attribute Name'),
            static::FIELD_ATTRIBUTE_VALUE          => _wpcc('Product Attribute Values'),
        ];
    }

    public function getInteractableFields() {
        return $this->getTransformableFields() + [
            static::FIELD_PRODUCT_URL    => _wpcc('Product URL'),
            static::FIELD_REGULAR_PRICE  => _wpcc('Product Regular Price'),
            static::FIELD_SALE_PRICE     => _wpcc('Product Sale Price'),
            static::FIELD_SKU            => _wpcc('Product SKU'),
            static::FIELD_STOCK_QUANTITY => _wpcc('Product Stock Quantity'),
            static::FIELD_WEIGHT         => _wpcc('Product Weight'),
            static::FIELD_LENGTH         => _wpcc('Product Length'),
            static::FIELD_WIDTH          => _wpcc('Product Width'),
            static::FIELD_HEIGHT         => _wpcc('Product Height'),
        ];
    }
}