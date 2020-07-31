<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CvsPayment\Api;

/**
 * Interface ConstantInterface
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Api
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
interface ConstantInterface
{
    const REGISTRY_PENDING_CVS_30_DAYS = 'riki_cvspayment_pending_cvs_30_days';
    const EMAIL_TEMPLATE_CANCEL_ORDER = 'riki_cvspayment_cancel_order_email_log';
    const CONFIG_PATH_CANCEL_DAYS = 'cvspayment/cvspayment/cancel_days';
    const CONFIG_PATH_CANCEL_CRON_SETTING
        = 'cvspayment/cvspayment/cancel_cron_setting';
    const CONFIG_PATH_CANCEL_EMAIL_NOTIFICATION
        = 'cvspayment/cvspayment/cancel_cron_email';
    const CONFIG_PATH_COMMAND_CREATE_ORDER_CVS_PAYMENT_SKU = 'cvspayment/command_create_cvs_payment_order/product_sku';
}
