<?php
namespace Riki\RmaWithoutGoods\Plugin\Rma\Block\Adminhtml\Rma\Create\Order\Grid;

class CreateReturnWithoutGood
{

    /**
     * @param \Magento\Rma\Block\Adminhtml\Rma\Create\Order\Grid $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $row
     * @return mixed
     */
    public function aroundGetRowUrl(
        \Magento\Rma\Block\Adminhtml\Rma\Create\Order\Grid $subject,
        \Closure $proceed,
        \Magento\Framework\DataObject $row
    ) {
        $isWgRequest = $subject->getRequest()->getParam('wg', 0);

        if ($isWgRequest) {
            return $subject->getUrl('rma_wg/rma/newAction', ['order_id' => $row->getId()]);
        }

        return $proceed($row);
    }
}
