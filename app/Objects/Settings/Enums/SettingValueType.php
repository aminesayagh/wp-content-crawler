<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 16/04/2019
 * Time: 18:40
 *
 * @since 1.9.0
 */

namespace WPCCrawler\Objects\Settings\Enums;


/**
 * Defines the constants that can be used to indicate a setting's data structure, i.e. the type of data a setting stores.
 *
 * @package WPCCrawler\Objects\Settings\Enums
 * @since   1.8.1
 */
class SettingValueType {

    const T_NO_VAL   = -1;
    const T_ARRAY    = 0;
    const T_STRING   = 1;
    const T_INTEGER  = 2;
    const T_BOOLEAN  = 3;
    const T_FLOAT    = 4;
    const T_DATE     = 5;

}