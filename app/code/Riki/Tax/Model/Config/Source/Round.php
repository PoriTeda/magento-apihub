<?php
/**
 * *
 *  Tax
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package  Riki\Tax
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Tax\Model\Config\Source;
/**
 * *
 *  Tax
 *
 *  @category RIKI
 *  @package  Riki\Tax\Model\Config\Source
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Round implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Retrieve possible customer address types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'round' => __('Round'),
            'ceil' => __('Ceil'),
            'floor' => __('Floor')
        ];
    }
}