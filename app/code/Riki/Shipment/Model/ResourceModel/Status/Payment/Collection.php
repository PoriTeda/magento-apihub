<?php
/**
 * Payment status
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status\Payment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\ResourceModel\Status\Payment;

/**
 * Class Collection
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status\Payment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Collection extends
    \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define variables
     *
     * @var string
     */
    protected $_idFieldName = 'payment_status_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Shipment\Model\Status\Payment',
            'Riki\Shipment\Model\ResourceModel\Status\Payment'
        );
        $this->_map['fields']['payment_status_id'] = 'main_table.payment_status_id';
    }


}