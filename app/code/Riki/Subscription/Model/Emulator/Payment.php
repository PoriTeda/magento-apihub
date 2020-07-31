<?php

namespace Riki\Subscription\Model\Emulator;

class Payment
    extends \Magento\Quote\Model\Quote\Payment
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Riki\Subscription\Model\Emulator\Helper\Payment $emulatorPaymentData,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $encryptor, $methodSpecificationFactory, $resource, $resourceCollection, $data);
        $this->_paymentData = $emulatorPaymentData;
    }


    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('\Riki\Subscription\Model\Emulator\ResourceModel\Payment');
    }

}