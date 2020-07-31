<?php

namespace Bluecom\PaymentCustomer\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context Context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Retrieve Customer Group
     *
     * @param string $code string
     *
     * @return mixed
     */
    public function getCustomerGroup($code)
    {
        $path = 'payment/' . $code . '/customergroup';
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Status
     *
     * @param string $code string
     *
     * @return mixed
     */
    public function getStatus($code)
    {
        $path = 'payment/' . $code . '/active';
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * To ArrayCustomerGroup
     *
     * @param array $dataGroups array
     *
     * @return array
     */
    public function toArrayCustomerGroup($dataGroups)
    {
        $data = trim($dataGroups, ',');
        $arrayGroup = explode(',', $data);
        return $arrayGroup;
    }
}