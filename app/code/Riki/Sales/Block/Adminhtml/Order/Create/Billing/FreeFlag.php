<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Billing;

class FreeFlag extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * @return mixed
     */
    public function isSelectedFree()
    {
        return $this->_sessionQuote->getFreeSurcharge();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        $parentBlock = $this->getParentBlock();
        $formBlock = $parentBlock->getChildBlock('form');
        $preferredPayment = $formBlock->getPreferredMethod();
        $order = $this->getCreateOrderModel();

        $paymentMethod = $order->getQuote()->getPayment()->getMethod();
        if (is_null($paymentMethod) || $paymentMethod == \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE) {
            $paymentMethod = $preferredPayment;
        }

        $allowedPayment = [
            \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD,
            \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];

        if (in_array($paymentMethod, $allowedPayment)) {
            return true;
        }

        return false;
    }
}
