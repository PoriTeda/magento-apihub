<?php
/**
 * Riki Sales calculate cut off date for Shipment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Sales\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Sales\Model\ResourceModel;
/**
 * Class OrderPayshipStatus
 *
 * @category  RIKI
 * @package   Riki\Sales\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class OrderPayshipStatus extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('riki_order_status', 'status_id');
    }

    /**
     * @param $orderId
     * @return bool|string
     */
    public function getRikiStatusOrderbyNumberOrder($orderId){
        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $select = $connection->select()->from($table)->columns('status_id')->where('order_id = ?',$orderId);
        $statusId = $connection->fetchOne($select);
        if(!$statusId){
            return false;
        }
        return $statusId;
    }
}