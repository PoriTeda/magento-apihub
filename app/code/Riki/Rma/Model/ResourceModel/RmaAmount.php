<?php
namespace Riki\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Riki\Rma\Model\Config\Source\Rma\ReturnStatus;

class RmaAmount extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_setMainTable('magento_rma');
    }

    /**
     * @param $orderId
     * @return int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTotalReturnsByOrder($orderId)
    {
        if ($orderId instanceof \Magento\Sales\Model\Order) {
            $orderId = $orderId->getId();
        }

        if ($orderId) {
            $conn = $this->getConnection();

            $select = $conn->select()->from(
                $this->getMainTable(),
                ['sum(total_return_amount_adjusted)']
            )->where(
                'order_id = ?',
                (int)$orderId
            )->where(
                'return_status IN (?)',
                [
                    ReturnStatus::REVIEWED_BY_CC,
                    ReturnStatus::APPROVED_BY_CC,
                    ReturnStatus::COMPLETED
                ]
            );

            return $conn->fetchOne($select);
        }

        return 0;
    }
}
