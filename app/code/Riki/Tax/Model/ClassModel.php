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

namespace Riki\Tax\Model;

/**
 * *
 *  Tax
 *
 *  @category RIKI
 *  @package  Riki\Tax\Model
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class ClassModel extends \Magento\Tax\Model\ClassModel
{
    /**
     * Defines Payment Tax Class string
     */
    const TAX_CLASS_NAME_PAYMENT = 'Payment';

    const TAX_CLASS_NAME_SHIPPING = 'Shipping';

    const TAX_CLASS_NAME_WRAPPING = 'GiftWrapping';

    /**
     * Defines Tax Rates Code string
     */
    const TAX_RATE_CODE_PAYMENT = 'Payment fee Rate';

    const TAX_RATE_CODE_SHIPPING = 'Shipping Fee Rate';

    const TAX_RATE_CODE_WRAPPING = 'Gift Wrapping Fee Rate';

    /**
     * Defines Tax Rules Code string
     */
    const TAX_RULE_CODE_PAYMENT = 'Payment tax';

    const TAX_RULE_CODE_SHIPPING = 'Shipping fee tax';

    const TAX_RULE_CODE_WRAPPING = 'Gift Wrapping Tax';
}