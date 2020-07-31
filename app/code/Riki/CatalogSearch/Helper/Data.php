<?php
/**
 * Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Module
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CatalogSearch\Helper;

class Data
{
    /**
     * Strip data
     *
     * @param string $string
     *
     * @return string
     */
    public function clean($string)
    {
//        if (preg_match("@^[a-zA-Z0-9%+-_]*$@", $string)) {
            // URL is urlencoded
        $string = urldecode($string);
//        }

        $string = preg_replace("/[+,!@#$%^&*();\/|<>\"'=:.{}]/u",'', $string); // Removes specific chars

        return trim($string);
    }

}