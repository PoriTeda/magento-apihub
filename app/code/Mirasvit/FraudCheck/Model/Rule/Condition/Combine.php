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

use Magento\Rule\Model\Condition\Combine as RuleConditionCombine;
use Magento\Rule\Model\Condition\Context;

/**
 * @method $this setType($type)
 * @method string getType()
 */
class Combine extends RuleConditionCombine
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var BillingFactory
     */
    private $billingFactory;

    /**
     * @var ShippingFactory
     */
    private $shippingFactory;

    public function __construct(
        OrderFactory $orderFactory,
        CustomerFactory $customerFactory,
        BillingFactory $billingFactory,
        ShippingFactory $shippingFactory,
        Context $context
    ) {
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->billingFactory = $billingFactory;
        $this->shippingFactory = $shippingFactory;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getNewChildSelectOptions()
    {
        $attributes = [];

        $orderAttributes = $this->orderFactory->create()->loadAttributeOptions()->getAttributeOption();
        foreach ($orderAttributes as $code => $label) {
            $attributes['Order'][] = [
                'value' => "Mirasvit\\FraudCheck\\Model\\Rule\\Condition\\Order|$code",
                'label' => $label,
            ];
        }

        $customerAttributes = $this->customerFactory->create()->loadAttributeOptions()->getAttributeOption();
        foreach ($customerAttributes as $code => $label) {
            $attributes['Customer'][] = [
                'value' => "Mirasvit\\FraudCheck\\Model\\Rule\\Condition\\Customer|$code",
                'label' => $label,
            ];
        }

        $billingAttributes = $this->billingFactory->create()->loadAttributeOptions()->getAttributeOption();
        foreach ($billingAttributes as $code => $label) {
            $attributes['Billing'][] = [
                'value' => "Mirasvit\\FraudCheck\\Model\\Rule\\Condition\\Billing|$code",
                'label' => $label,
            ];
        }

        $shippingAttributes = $this->shippingFactory->create()->loadAttributeOptions()->getAttributeOption();
        foreach ($shippingAttributes as $code => $label) {
            $attributes['Shipping'][] = [
                'value' => "Mirasvit\\FraudCheck\\Model\\Rule\\Condition\\Shipping|$code",
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();

        $conditions = array_merge_recursive($conditions, [
            [
                'value' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Combine',
                'label' => __('Conditions Combination'),
            ],
        ]);

        foreach ($attributes as $group => $arrAttributes) {
            $conditions = array_merge_recursive($conditions, [
                [
                    'label' => $group,
                    'value' => $arrAttributes,
                ],
            ]);
        }

        return $conditions;
    }

    /**
     * {@inheritdoc}
     */
    public function collectValidatedAttributes($productCollection)
    {
        return $this;
    }
}
