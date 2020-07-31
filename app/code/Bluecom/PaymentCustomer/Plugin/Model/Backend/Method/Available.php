<?php

namespace Bluecom\PaymentCustomer\Plugin\Model\Backend\Method;

class Available
{
    /**
     * @var \Bluecom\PaymentCustomer\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * Available constructor.
     *
     * @param \Bluecom\PaymentCustomer\Helper\Data $helperData   Data
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote Quote
     */
    public function __construct(
        \Bluecom\PaymentCustomer\Helper\Data $helperData,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ) {
        $this->_helperData = $helperData;
        $this->_sessionQuote = $sessionQuote;
    }

    /**
     * Check current customer group.
     *
     * @param \Magento\Payment\Model\Method\AbstractMethod $subject AbstractMethod
     * @param object                                       $result  Object
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterIsAvailable(\Magento\Payment\Model\Method\AbstractMethod $subject, $result)
    {
        if ($subject->getCode() == 'free') {
            //Zero Subtotal Checkout
            return $result;
        }

        $currentCustomerGroup = $this->_sessionQuote->getQuote()->getData('customer_group_id');
        $customerGroups = $this->_helperData->getCustomerGroup($subject->getCode());
        $dataGroups = $this->_helperData->toArrayCustomerGroup($customerGroups);
        if (!in_array($currentCustomerGroup, $dataGroups)) {
            return $result = false;
        }
        return $result;
    }
}