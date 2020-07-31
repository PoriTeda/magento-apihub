<?php
namespace Riki\Fraud\Controller\Adminhtml\Cedyna;

class Validate extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * @var \Riki\Fraud\Helper\CedynaThreshold
     */
    protected $_cedynaHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Riki\Fraud\Helper\CedynaThreshold $cedynaThreshold
    ){
        parent::__construct($context);
        $this->_jsonHelper = $jsonHelper;
        $this->_sessionQuote = $sessionQuote;
        $this->_cedynaHelper = $cedynaThreshold;
    }

    public function execute()
    {
        $error = false;

        $message = '';

        if ($data = $this->getRequest()->getParams()) {
            $customerId = $this->getRequest()->getParam('customerId');
            if ($this->_cedynaHelper->isCedynaCustomerById($customerId)) {
                $quote = $this->_sessionQuote->getQuote();
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
                if ($quote->isVirtual()) {
                    $totals = $quote->getBillingAddress()->getTotals();
                } else {
                    $totals = $quote->getShippingAddress()->getTotals();
                }

                if (!empty($totals) && !empty($totals['grand_total'])) {
                    $grandTotal = $totals['grand_total']->getValue();
                    $exceed = $this->_cedynaHelper->willCedynaThresholdExceed($customerId, $grandTotal);
                    if ($exceed) {
                        $error =  true;
                        $message = __('The amount will exceed the cedyna threshold, please confirm you want to proceed the order.');
                    }
                }
            }
        }

        $res = $this->_jsonHelper->jsonEncode([
            'error' => $error,
            'message' => $message
        ]);

        $this->getResponse()->representJson($res);
    }

    /**
     * Check if admin has permissions to visit related pages
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Fraud::suspected_fraud');
    }
}
