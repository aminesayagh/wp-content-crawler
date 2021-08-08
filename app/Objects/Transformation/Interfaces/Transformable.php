<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 17/02/2019
 * Time: 18:43
 *
 * @since 1.9.0
 */

namespace WPCCrawler\Objects\Transformation\Interfaces;

/**
 * @package WPCCrawler\Objects\Transformation\Interfaces
 * @since   1.8.1
 */
interface Transformable {

    /**
     * Returns an array that stores the names of the fields that can be transformed. The values indicate the transformable
     * keys of the fields. E.g. if the value of "attachmentData" field has a an array value, whose each item has
     * transformable values in "title" and "alt" keys,
     *
     * <p>
     * <i>["attachmentData.title" => 'Attachment Title', "attachmentData.alt" => 'Attachment Alt Text']</i>
     * <p>
     *
     * indicates this. Make sure the value is defined for a transformable field. If you do not specify a value, the field
     * won't be considered as transformable. So, <i>["title"]</i> is not transformable, while <i>["title" => ""]</i> is
     * transformable. Hence, this must be an associative array. If a key points to an associative array and no key is
     * defined for the array, all keys of the array are transformable. For example, if <i>["data" => "Post Data"]</i> is
     * given and <i>getData()</i> method returns <i>[ ['name' => 'A', 'value' => 'B'] ]</i>, then the values of both
     * <i>'name'</i> and <i>'value'</i> keys are transformable. If <i>["data.name" => "Post Data"]</i> is given, then
     * only the value of <i>'name'</i> key is transformable. Objects are transformable as well. In case of objects, set
     * the field names that have setter and getter methods. E.g. if <i>"attachmentData"</i> stores an array of MediaFile
     * instances, and each media file has a mediaTitle field and <i>setMediaTitle</i> and <i>getMediaTitle</i> methods,
     * then <i>["attachmentData.mediaTitle" => "..."]</i> indicates this.
     *
     * NOTE: Transform more wisely. For example, instead of transforming listNumbers, listTitles, etc., just transform
     * the final post template. By this way, the number of chars to be transformed will be less, hence, less money will
     * be spent for the transformation service.
     *
     * NOTE: The fields must have mutator and accessor methods. In other words, if there is "title", then there must be
     * <i>setTitle($title)</i> and <i>getTitle()</i> methods so that "title" can be transformed. The methods must start with
     * "set" and "get", respectively, and they must be named in camelCase.
     *
     * @return array
     * @since 1.9.0
     */
    public function getTransformableFields();

    /**
     * Returns an array in the same structure as {@link getTransformableFields()}. This method defines the fields that
     * can be interacted with. For example, if certain things need to be found and replaced, the values of these fields
     * can be retrieved and changed by another method. Another example might be parsing short codes in all the values
     * of all the fields. So, these fields can be queried and changed by a method that wants to do so. While the fields
     * returned by {@link getTransformableFields()} are generally used to translate and spin, the fields returned by
     * this method can be used for anything.
     *
     * @return array
     * @since 1.9.0
     */
    public function getInteractableFields();

}