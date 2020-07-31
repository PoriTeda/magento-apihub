<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Observer\Admin;

use Magento\Framework\Event\ObserverInterface;

class FormCreationObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_objectManager = $objectManager;
        $this->_coreRegistry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $actionsSelect = $observer->getForm()->getElement('simple_action');
        if ($actionsSelect){
            $vals = $actionsSelect->getValues();
            $vals[] = [
                'value' => 'ampromo_items',
                'label' => __('Auto add promo items with products'),

            ];
            $vals[] = [
                'value' => 'ampromo_cart',
                'label' => __('Auto add promo items for the whole cart'),
            ];
            $vals[] = [
                'value' => 'ampromo_product',
                'label' => __('Auto add the same product'),
            ];
            $vals[] = [
                'value' => 'ampromo_spent',
                'label' => __('Auto add promo items for every $X spent'),
            ];

            $actionsSelect->setValues($vals);

            $fldSet = $observer->getForm()->getElement('actions_fieldset');
            $fldSet->addField('ampromo_type', 'select', [
                'name'      => 'ampromorule[type]',
                'label'     => __('Type'),
                'values'    => [
                    0 => __('All SKUs below'),
                    1 => __('One of the SKUs below')
                ],
            ],
                'discount_amount'
            );
            $fldSet->addField('ampromo_sku', 'text', [
                'name'  => 'ampromorule[sku]',
                'label' => __('Promo Items'),
                'note'  => __('Comma separated list of the SKUs'),
            ],
                'ampromo_type'
            );
        }

        $salesrule = $this->_coreRegistry->registry('current_promo_sales_rule');

        $ruleId = $salesrule->getId();
        $ampromoRule = $this->_objectManager->get('Amasty\Promo\Model\Rule');
        $ampromoRule->load($ruleId, 'salesrule_id');

        $salesrule->addData([
            'ampromo_type' => $ampromoRule->getData('type'),
            'ampromo_sku' => $ampromoRule->getData('sku')
        ]);
    }
}
