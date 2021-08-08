<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/cloud/translate/v3/translation_service.proto

namespace Google\Cloud\Translate\V3;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for ListGlossaries.
 *
 * Generated from protobuf message <code>google.cloud.translation.v3.ListGlossariesResponse</code>
 */
class ListGlossariesResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * The list of glossaries for a project.
     *
     * Generated from protobuf field <code>repeated .google.cloud.translation.v3.Glossary glossaries = 1;</code>
     */
    private $glossaries;
    /**
     * A token to retrieve a page of results. Pass this value in the
     * [ListGlossariesRequest.page_token] field in the subsequent call to
     * `ListGlossaries` method to retrieve the next page of results.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     */
    private $next_page_token = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Cloud\Translate\V3\Glossary[]|\Google\Protobuf\Internal\RepeatedField $glossaries
     *           The list of glossaries for a project.
     *     @type string $next_page_token
     *           A token to retrieve a page of results. Pass this value in the
     *           [ListGlossariesRequest.page_token] field in the subsequent call to
     *           `ListGlossaries` method to retrieve the next page of results.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Cloud\Translate\V3\TranslationService::initOnce();
        parent::__construct($data);
    }

    /**
     * The list of glossaries for a project.
     *
     * Generated from protobuf field <code>repeated .google.cloud.translation.v3.Glossary glossaries = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getGlossaries()
    {
        return $this->glossaries;
    }

    /**
     * The list of glossaries for a project.
     *
     * Generated from protobuf field <code>repeated .google.cloud.translation.v3.Glossary glossaries = 1;</code>
     * @param \Google\Cloud\Translate\V3\Glossary[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setGlossaries($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Cloud\Translate\V3\Glossary::class);
        $this->glossaries = $arr;

        return $this;
    }

    /**
     * A token to retrieve a page of results. Pass this value in the
     * [ListGlossariesRequest.page_token] field in the subsequent call to
     * `ListGlossaries` method to retrieve the next page of results.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @return string
     */
    public function getNextPageToken()
    {
        return $this->next_page_token;
    }

    /**
     * A token to retrieve a page of results. Pass this value in the
     * [ListGlossariesRequest.page_token] field in the subsequent call to
     * `ListGlossaries` method to retrieve the next page of results.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setNextPageToken($var)
    {
        GPBUtil::checkString($var, True);
        $this->next_page_token = $var;

        return $this;
    }

}
