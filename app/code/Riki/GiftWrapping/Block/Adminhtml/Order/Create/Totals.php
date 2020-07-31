<?php
namespace Riki\GiftWrapping\Block\Adminhtml\Order\Create;

class Totals extends \Magento\GiftWrapping\Block\Adminhtml\Order\Create\Totals
{
    /**
     * Return information for showing
     *
     * @return array
     */
    public function getValues()
    {
        $values = [];
        $total = $this->getTotal();
        $totals = $this->_giftWrappingData->getTotals($total);
        foreach ($totals as $total) {
            $label = $total['label'];
            if($label instanceof \Magento\Framework\Phrase)
                $label = $label->getText();
            $values[$label] = $total['value'];
        }
        return $values;
    }
}
