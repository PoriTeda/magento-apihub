<?php
namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Order;

class Totals
{
    /**
     * remove default gift wrapping fee block from total info
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Totals $subject
     * @param null $area
     * @return array
     */
    public function beforeGetTotals(
        \Magento\Sales\Block\Adminhtml\Order\Totals $subject,
        $area = null
    )
    {
        if($subject->getParentBlock() instanceof \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info){
            $subject->removeTotal('gw_items_excl');
            $subject->removeTotal('gw_items_incl');
            $subject->removeTotal('gw_items');
            $subject->removeTotal('gw_order_excl');
            $subject->removeTotal('gw_order_incl');
            $subject->removeTotal('gw_order');
        }

        return [$area];
    }
}