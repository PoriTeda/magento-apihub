<?php
namespace Riki\Fraud\Block\Adminhtml\Order\Create;
class Cedyna extends \Magento\Framework\View\Element\Template
{
    public function _prepareLayout(){
        $this->setTemplate('Riki_Fraud::order/create.phtml');
    }

    /**
     * @return string
     */
    public function getCedynaUrl()
    {
        return $this->getUrl('riki_fraud/cedyna/validate');
    }

    /**
     * @return string
     */
    public function getInvoicedPaymentCode()
    {
        return \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED;
    }
}