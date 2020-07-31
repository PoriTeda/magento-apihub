<?php
namespace Riki\RmaWithoutGoods\Plugin\Rma\Block\Adminhtml\Rma\Create\Order\Grid;

class ChangeSearchCondition
{
    public function beforeAddColumn(
        \Magento\Rma\Block\Adminhtml\Rma\Create\Order\Grid $subject,
        $columnId,
        $column
    ) {
        if (is_array($column)) {
            if (in_array($columnId, [
                'real_order_id',
                'customer_lastname',
                'customer_firstname'
            ])) {
                $column['filter_condition_callback'] = [$this, 'filterKeyTextSearch'];
            }
        }

        return [$columnId, $column];
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @return $this
     */
    public function filterKeyTextSearch($collection, $column)
    {
        $collection->addFieldToFilter($column->getIndex(), $column->getFilter()->getValue());
        return $this;
    }
}
