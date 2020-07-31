<?php
/**
 * Payment Status
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\ResourceModel\Status;
/**
 * Class Payment
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Payment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_shipment_payment_history', 'payment_status_id');
    }
}