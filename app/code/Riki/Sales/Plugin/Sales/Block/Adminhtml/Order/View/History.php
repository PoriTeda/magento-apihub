<?php
namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Order\View;

class History
{
    /**
     * remove NEW_ORDER status from status list of state
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View\History $subject
     * @param array $result
     * @return array
     */
    public function afterGetStatuses(
        \Magento\Sales\Block\Adminhtml\Order\View\History $subject,
        array $result
    ){
        foreach($result as $_code => $_label){
            if($_code == 'pending'){
                unset($result[$_code]);
                break;
            }
        }

        return $result;
    }
}