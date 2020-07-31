<?php
/**
 * Status Resource Model
 *
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
 * Class Shipment
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Shipment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('riki_shipment_shipping_history', 'shipment_status_id');
    }
}