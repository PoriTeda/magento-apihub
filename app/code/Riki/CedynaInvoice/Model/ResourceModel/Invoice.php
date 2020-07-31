<?php
namespace Riki\CedynaInvoice\Model\ResourceModel;

class Invoice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_cedyna_invoice', 'id');
    }

    /**
     * @param $customerId
     * @return array
     */
    public function getInvoicesByCustomer($customerId)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from(
            $this->getMainTable(),
            [
                'import_month',
                'target_month',
                'SUM( CASE WHEN `data_type` = "01" THEN `row_total` ELSE (-1)*`row_total` END ) AS total',
                'MAX(riki_cedyna_invoice.created_at) as import_date'
            ]
        );
        $sqlSelect->where('customer_id = ?', $customerId);
        $sqlSelect->group(['import_month', 'target_month']);
        $sqlSelect->order(['import_month DESC']);
        $sqlSelect->limit(24);
        return $connection->fetchAll($sqlSelect);
    }
    /**
     * @param $customerId
     * @return array
     */
    public function getMonthlyInvoicesByCustomer($customerId, $targetMonth)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from(
            $this->getMainTable(),
            [
                'import_month',
                'target_month',
                'row_total',
                'import_date',
                'increment_id',
                'product_line_name',
                'unit_price',
                'qty',
                'data_type',
                'riki_nickname',
                'order_created_date',
                'shipped_out_date',
                'returned_date'
            ]
        );
        $sqlSelect->where('customer_id = ?', $customerId);
        $sqlSelect->where('target_month = ?', $targetMonth);
        $sqlSelect->order('order_created_date ASC');
        return $connection->fetchAll($sqlSelect);
    }
}
