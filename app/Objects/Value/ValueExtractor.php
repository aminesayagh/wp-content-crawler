<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 14/12/2018
 * Time: 10:07
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\Value;


use WPCCrawler\Exceptions\MethodNotExistException;

class ValueExtractor {

    private $separator = '.';

    /**
     * Fills the given map with the data extracted from given $value. The resultant array is a flat array where
     * keys are dot notation keys and the values are their values retrieved from the given $value.
     *
     * @param mixed  $value     The value from which the data will be extracted to fill the given map.
     * @param array $map        An associative array showing the fields from which the data will be extracted. The
     *                          fields must be specified in dot notation. For example, "post.title" will extract the
     *                          value of the title field that exists in the value of post field. Here, the given object
     *                          has a "post" value and "getPost" method that returns an object having a "title" field
     *                          and "getTitle" method. This map must be associative. In other words, the dot notations
     *                          must be provided as keys of the given array.
     * @param string $separator Separator used in the dot notation.
     * @return array|null       Flattened array where keys are dot keys and the values are their corresponding values
     *                          extracted from $value.
     * @throws MethodNotExistException See {@link getForObject()}
     * @since 1.8.0
     */
    public function fillAndFlatten($value, $map, $separator = '.') {
        $this->separator = $separator;

        // If there is no map, return null.
        if (!$map) return null;

        // Prepare the map such that the keys have the given separator instead of dot key.
        $map = $this->prepareMap($map);

        // Now, we will fill the values of the prepared map by getting them from the given $value.
        $results = [];
        foreach($map as $dotKey => $val) {
            // There must be a dot key.
            if ($dotKey === null || $dotKey === '') continue;

            // Extract the values from the given $value for this dot key
            $res = $this->getResult($value, $dotKey);

            // If there is no result, continue with the next one.
            if (!$res) continue;

            // Add the results
            $results = array_merge($results, $res);
        }

        return $results;
    }

    /*
     * MAP PREPARER METHOD
     */

    /**
     * Changes the dots in the given dot keys into the value specified with {@link separator}. For example, if the
     * separator is "|", then this array ['item1.item2' => 'Val 1', 'item1.item3.item4' => 'Val 2'] becomes
     * ['item1|item2' => 'Val 1', 'item1|item3|item4' => 'Val 2']
     *
     * @param null|string|array $map The map to be prepared, such as {@link Transformable::getTransformableFields()}
     * @return null|array Prepared map or null.
     * @since 1.8.0
     */
    private function prepareMap($map) {
        $prepared = [];
        foreach($map as $dotKey => $description) {
            $prepared[str_replace('.', $this->separator, $dotKey)] = $description;
        }

        return $prepared;
    }

    /*
     * MAP FILLER METHODS
     */

    /**
     * Extract data from a value by using dot notation.
     *
     * @param mixed       $value        Value of dot key will be extracted from this.
     * @param string      $dotKey       A dot notation that will be used to extract the data from $value.
     * @param null|string $parentKey    Parent dot key for $dotKey, if exists. When flattening, the found item will be
     *                                  added under the parent key.
     * @return array|null Flattened array where keys are dot keys and the values are their corresponding values.
     * @since 1.8.0
     * @throws MethodNotExistException See {@link getForObject()}
     */
    private function getResult($value, $dotKey, $parentKey = null) {
        // If the value is an object
        if (is_object($value)) {
            $res = $this->getForObject($value, $dotKey, $parentKey);

        // If it is an array
        } else if (is_array($value)) {
            $res = $this->getForArray($value, $dotKey, $parentKey);

        // Otherwise
        } else {
            $dotKeyParts = explode($this->separator, $dotKey);
            $firstKey    = array_shift($dotKeyParts);

            $res = $this->getForString($value, $firstKey, $parentKey);
        }

        return $res;
    }

    /**
     * Extract data from an object by using dot notation.
     *
     * @param object      $object       Value of dot key will be extracted from this.
     * @param string      $dotKey       A dot notation that will be used to extract the data from $value.
     * @param null|string $parentKey    Parent dot key for $dotKey, if exists. When flattening, the found item will be
     *                                  added under the parent key.
     * @return array|null Flattened array where keys are dot keys and the values are their corresponding values.
     * @since 1.8.0
     * @throws MethodNotExistException If a getter method does not exist in the given object's class.
     */
    private function getForObject($object, $dotKey, $parentKey = null) {
        if (!$dotKey) return null;

        // Get first key in the dot notation and the remaining keys (the dot key that does not include the first key)
        $dotKeyParts     = explode($this->separator, $dotKey);
        $firstKey        = array_shift($dotKeyParts);
        $remainingDotKey = implode($this->separator, $dotKeyParts);

        // To get the value, we need the getter method's name.
        $getterMethodName = $this->getGetterMethodName($firstKey);

        // If the method does not exist in the object, throw an exception.
        if(!method_exists($object, $getterMethodName)) {
            throw new MethodNotExistException(sprintf('%1$s method does not exist in %2$s', $getterMethodName, get_class($object)));
        }

        // Get the value by calling the getter
        $value = $object->$getterMethodName();

        // Prepare the item's parent key
        $parentKey = $parentKey !== null && $parentKey !== '' ? $parentKey . $this->separator . $firstKey : $firstKey;

        return $this->getResult($value, $remainingDotKey, $parentKey);

    }

    /**
     * Extract data from an array by using dot notation.
     *
     * @param array       $arr          Value of dot key will be extracted from this.
     * @param string      $dotKey       A dot notation that will be used to extract the data from $value.
     * @param null|string $parentKey    Parent dot key for $dotKey, if exists. When flattening, the found item will be
     *                                  added under the parent key.
     * @return array|null Flattened array where keys are dot keys and the values are their corresponding values.
     * @since 1.8.0
     * @throws MethodNotExistException See {@link getForObject()}
     */
    private function getForArray($arr, $dotKey, $parentKey = null) {
        // Get first key in the dot notation and the remaining keys (the dot key that does not include the first key)
        $dotKeyParts     = explode($this->separator, $dotKey);
        $firstKey        = array_shift($dotKeyParts);
        $remainingDotKey = implode($this->separator, $dotKeyParts);

        // If there is a dot key and the array is an associative array
        if ($dotKey !== null && $dotKey !== '' && isset($arr[$firstKey])) {
            $parentKey = $parentKey ? $parentKey . $this->separator . $firstKey : $firstKey;

            // Prepare the value at $firstKey index of the array
            $res = $this->getResult($arr[$firstKey], $remainingDotKey, $parentKey);

            return $res;

        } else {
            $results = [];

            // Array does not have keys. It is a sequential array.
            foreach($arr as $i => $value) {
                // Prepare it by adding the index to the parent key
                $res = $this->getResult($arr[$i], $dotKey, $parentKey . $this->separator . $i);
                if (!$res) continue;

                // Collect the results
                $results = array_merge($results, $res);
            }

            return $results;
        }
    }

    /**
     * Extract data from a value that is not an array or an object by using dot notation.
     *
     * @param null|string|float|double $value     Value of dot key will be extracted from this.
     * @param string                   $dotKey    A dot notation that will be used to extract the data from $value.
     * @param null|string              $parentKey Parent dot key for $dotKey, if exists. When flattening, the found
     *                                            item will be added under the parent key.
     * @return array|null Flattened array where keys are dot keys and the values are their corresponding values.
     * @since 1.8.0
     */
    private function getForString($value, $dotKey, $parentKey) {
        // Merge the parent key and the dot key to create a combined dot key
        $key = implode($this->separator, array_filter([$parentKey, $dotKey], function($v) {
            return $v !== null && $v !== '';
        }));

        // If the value is numeric, empty or null, or the key does not exist, return null.
        if (is_numeric($value) || $value === '' || $value === null || $key === null || $key === '') return null;

        // Create a 1-item associative array with the key and the value.
        return [$key => $value];
    }

    /*
     * OTHER HELPERS
     */

    /**
     * Get getter method name of an object's field
     *
     * @param string $fieldName Field name of an object.
     * @return string Name of the getter method that should return the value of given $fieldName
     * @since 1.8.0
     */
    private function getGetterMethodName($fieldName) {
        return "get" . ucfirst($fieldName);
    }

}