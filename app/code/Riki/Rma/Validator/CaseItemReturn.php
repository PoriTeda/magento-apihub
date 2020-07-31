<?php

namespace Riki\Rma\Validator;

class CaseItemReturn extends \Magento\Framework\Validator\AbstractValidator
{
    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * CaseItemReturn constructor.
     * @param \Magento\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Rma\Helper\Data $dataHelper
    )
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function isValid($rma)
    {

        if ($rma->getIncrementId()) { // only validate for create case
            return true;
        }

        $orderItemCollection = $this->dataHelper->getOrderItems($rma->getOrderId());

        $caseItems = [];

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($orderItemCollection as $orderItem) {
            if ($orderItem->getData('unit_case') == \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_CASE) {
                $caseItems[$orderItem->getId()] = $orderItem->getData('unit_qty');
            }
        }

        $rmaItems = $rma->getItems();

        /** @var \Magento\Rma\Model\Item $rmaItem */
        foreach ($rmaItems as $rmaItem) {

            $orderItemId = $rmaItem->getOrderItemId();
            if (array_key_exists($orderItemId, $caseItems)) {
                if ($rmaItem->getQtyRequested() % $caseItems[$orderItemId] != 0) {
                    $this->_addMessages(['case product' =>  __('Request qty of case product %1 is invalid', $rmaItem->getData('product_sku'))]);
                    return false;
                }
            }
        }

        return true;
    }
}
