<?php
namespace Riki\Rule\Model\ResourceModel;

class OrderSapBooking extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_sales_order_sap_booking', 'id');
    }

    /**
     * Insert multiple data
     *
     * @param array $data array
     *
     * @return $this
     * @throws \Exception
     */
    public function multiplyBunchInsert($data)
    {
        $this->getConnection()->beginTransaction();

        try {

            $this->getConnection()->insertMultiple(
                $this->getTable('riki_sales_order_sap_booking'),
                $data
            );
            $this->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        return $this;
    }
}