<?php
/**
 * Directory
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Directory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Directory\Plugin;

use Magento\Directory\Model\Currency;

/**
 * ModifyCurrencyFormat
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Directory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class ModifyCurrencyFormat
{
    /**
     * Move symbol to end of price
     *
     * @param Currency $subject Currency
     * @param string   $value   string
     * @param array    $options array
     *
     * @return array
     */
    public function beforeFormatTxt(Currency $subject, $value, $options = [])
    {
        if ($subject->getCode() == 'JPY') {
            $options['format'] = '#,##0.00¤';
        }

        return [$value, $options];
    }

    /**
     * FormatPrecision
     *
     * @param Currency $subject          Currency
     * @param \Closure $proceed          Closure
     * @param string   $price            price
     * @param int      $precision        precision
     * @param array    $options          options
     * @param bool     $includeContainer includeContainer
     * @param bool     $addBrackets      addBrackets
     *
     * @return mixed
     */
    public function aroundFormatPrecision(
        Currency $subject,
        \Closure $proceed,
        $price,
        $precision,
        $options = [],
        $includeContainer = true,
        $addBrackets = false
    ) {
        if ($subject->getCode() == 'JPY') {
            $precision = '0';
            if (isset($options['precision'])) {
                $options['precision'] = '0';
            }
        }
        return $proceed($price, $precision, $options, $includeContainer, $addBrackets);
    }
}
