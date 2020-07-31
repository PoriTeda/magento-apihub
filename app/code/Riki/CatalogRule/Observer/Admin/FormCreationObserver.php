<?php
namespace Riki\CatalogRule\Observer\Admin;

use Magento\Framework\Event\ObserverInterface;

class FormCreationObserver implements ObserverInterface
{
    protected $_coreRegistry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof \Magento\CatalogRule\Block\Adminhtml\Promo\Catalog\Edit\Tab\Actions) {
            $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
            $fieldset = $block->getForm()->getElement('actions_fieldset');

            /*$fieldset->addField(
                'wbs',
                'text',
                [
                    'name' => 'wbs',
                    'label' => __('WBS'),
                    'class' => 'validate-wbs-code'
                ],
                'discount_amount'
            );

            $fieldset->addField(
                'account_code',
                'text',
                [
                    'name' => 'account_code',
                    'label' => __('Account code'),
                ],
                'wbs'
            );*/

            $fieldset->addField(
                'sap_condition_type',
                'text',
                [
                    'name' => 'sap_condition_type',
                    'label' => __('SAP Condition Type')
                ],
                'account_code'
            );

            $fieldset->getForm()->addValues([
                'wbs' => $model->getData('wbs'),
                'account_code' => $model->getData('account_code'),
                'sap_condition_type' => $model->getData('sap_condition_type'),
            ]);

            if ($model->isReadonly()) {
                foreach ($fieldset->getElements() as $element) {
                    $element->setReadonly(true, true);
                }
            }
        }
    }
}