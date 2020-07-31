<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Model\Rule\Condition;

use Magento\Customer\Model\AddressFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\Context;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @method string getAttribute()
 * @method $this setAttributeOption($attributes)
 * @method array getAttributeOption()
 */
class Billing extends AbstractCondition
{
    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        AddressFactory $addressFactory,
        PaymentConfig $paymentConfig,
        ScopeConfigInterface $scopeConfig,
        Context $context
    ) {
        $this->addressFactory = $addressFactory;
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfig = $scopeConfig;

        return parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'payment_method' => __('Billing: Payment Method'),
        ];

        $addressAttributes = $this->addressFactory->create()->getAttributes();
        foreach ($addressAttributes as $attr) {
            if ($attr->getStoreLabel() && $attr->getAttributeCode()) {
                $attributes[$attr->getAttributeCode()] = __('Billing: ') . $attr->getStoreLabel();
            }
        }

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'payment_method':
                $type = 'select';
                break;

            default:
                $type = 'string';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'payment_method':
                $type = 'select';
                break;
            default:
                $type = 'text';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getValueOption()
    {
        $options = [];

        if ($this->getAttribute() === 'payment_method') {
            foreach ($this->paymentConfig->getActiveMethods() as $code => $method) {
                $options[$code] = $this->scopeConfig->getValue("payment/$code/title");
            }
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(AbstractModel $object)
    {
        $attrCode = $this->getAttribute();

        /** @var \Magento\Sales\Model\Order $object */
        $address = $object->getBillingAddress();

        if ($address) {
            $value = $address->getData($attrCode);
        } else {
            $value = false;
        }

        if ($attrCode === 'payment_method') {
            $payment = $object->getPayment();

            if ($payment) {
                $value = $payment->getMethod();
            }
        }

        return parent::validateAttribute($value);
    }
}
