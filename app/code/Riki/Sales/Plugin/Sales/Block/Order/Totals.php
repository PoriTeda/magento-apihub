<?php
namespace Riki\Sales\Plugin\Sales\Block\Order;

class Totals
{
    /**
     * @param \Magento\Sales\Block\Order\Totals $subject
     * @param \Magento\Framework\DataObject $total
     * @param null $after
     * @return array
     */
    public function beforeAddTotal(
        \Magento\Sales\Block\Order\Totals $subject,
        \Magento\Framework\DataObject $total,
        $after = null
    ){
        if($total->getData('code') == 'grand_total_incl'){
            $after = 'last';
        }

        return [$total, $after];
    }

    /**
     * remove rule label from discount label
     *
     * @param \Magento\Sales\Block\Order\Totals $subject
     * @param null $area
     * @return array
     */
    public function beforeGetTotals(
        \Magento\Sales\Block\Order\Totals $subject,
        $area = null
    )
    {
        if($subject->getTotal('discount')){
            $subject->getTotal('discount')->setLabel(__('Discount'));
        }

        return [$area];
    }
}