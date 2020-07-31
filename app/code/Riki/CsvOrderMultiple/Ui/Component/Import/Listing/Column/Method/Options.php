<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\CsvOrderMultiple\Ui\Component\Import\Listing\Column\Method;

/**
 * Class Options
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * Constructor
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->paymentHelper = $paymentHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            //$option = $this->paymentHelper->getPaymentMethodList(true, true);
            //$this->options = $this->paymentHelper->getPaymentMethodList(true, true);

            return [
                [
                    'value'=>'cashondelivery',
                    'label'=>$this->geyPaymentTitle('cashondelivery')
                ],
                [
                    'value'=>'free',
                    'label'=>$this->geyPaymentTitle('free')
                ],
                [
                    'value'=>'invoicedbasedpayment',
                    'label'=>$this->geyPaymentTitle('invoicedbasedpayment')
                ],
                [
                    'value'=>'cvspayment',
                    'label'=>$this->geyPaymentTitle('cvspayment')
                ]
            ];
        }
        return $this->options;
    }

    /**
     * @param $paymentMethod
     * @return mixed
     */
    public function geyPaymentTitle($paymentMethod)
    {
        return $paymentOption = $this->scopeConfig->getValue('payment/' . $paymentMethod . '/title');
    }



}
