<?php
namespace Riki\RmaWithoutGoods\Plugin\RikiRma\Observer;

class ValidateData
{
    /**
     * @param \Riki\Rma\Observer\ValidateData $subject
     * @param \Closure $proceed
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool|mixed
     */
    public function aroundValidateOrderPayment(
        \Riki\Rma\Observer\ValidateData $subject,
        \Closure $proceed,
        \Magento\Rma\Model\Rma $rma
    ) {
        if($rma && $rma->getIsWithoutGoods()){
            return true;
        }

        return $proceed($rma);
    }
}
