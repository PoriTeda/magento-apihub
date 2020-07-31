<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Model\Course\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Payment
 */
class Payment implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    protected $_courseModel;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_courseModel = $courseModel;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $activePaymentNames = $this->_scopeConfig->getValue('payment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
        foreach ($activePaymentNames as $code => $data) {
            if($code == 'free') {
                // Not Show Zero Subtotal Checkout
                continue;
            }
            if (isset($data['active']) && (bool)$data['active'] && isset($data['model'])) {
                $options[] = [
                    'label' => $data['title'],
                    'value' => $this->_courseModel->mapPaymentMethod($code, true)
                ];
            }
        }
        return $options;
    }

    /**
     * Get payment name based on payment code
     *
     * @param string $paymentCode
     * @return string $paymentName
     */
    public function getOptionName($paymentCode)
    {
        $paymentName = $paymentCode;
        $activePaymentNames = $this->_scopeConfig->getValue('payment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
        foreach ($activePaymentNames as $code => $data) {
            if (isset($data['active']) && (bool)$data['active'] && isset($data['model']) && $paymentCode == $code) {
                $paymentName = $data['title'];
            }
        }
        return $paymentName;
    }
}
