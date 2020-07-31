<?php

namespace Bluecom\PaymentFee\Model\Config\Source\Payment;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\Config;

class AvailableMethods implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    protected $_appConfigScope;

    /**
     * Payment config
     *
     * @var Config
     */
    protected $_paymentConfig;

    /**
     * Init
     *
     * @param ScopeConfigInterface $configInterface scope config
     * @param config               $config          config
     */
    public function __construct(
        ScopeConfigInterface $configInterface,
        Config $config
    ) {
        $this->_appConfigScope = $configInterface;
        $this->_paymentConfig = $config;
    }

    /**
     * To Options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $payments = $this->_paymentConfig->getActiveMethods();
        $methods = [];

        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->_appConfigScope->getValue('payment/' . $paymentCode . '/title');
            $methods[$paymentCode] = [
                'label' => $paymentTitle,
                'value' => $paymentCode
            ];
        }
        return $methods;
    }

    /**
     * Get Payment Code
     *
     * @return array
     */
    public function getPaymentCodeArray()
    {
        $payments = $this->_paymentConfig->getActiveMethods();
        $methods = [];

        foreach ($payments as $paymentCode => $paymentModel) {
            $methods[] = $paymentCode;
        }
        return $methods;
    }
}