<?php
namespace Riki\Promo\Observer\Admin;

use Magento\Framework\Event\ObserverInterface;

class FormCreationObserver implements ObserverInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    protected $_yesNoSource;

    protected $_promoRuleFactory;

    public function __construct(
        \Magento\Config\Model\Config\Source\Yesno $yesNo,
        \Amasty\Promo\Model\Rule $rule,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_coreRegistry = $registry;
        $this->_yesNoSource = $yesNo;
        $this->_promoRuleFactory = $rule;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $actionsSelect = $observer->getForm()->getElement('simple_action');
        if ($actionsSelect){

            $fldSet = $observer->getForm()->getElement('actions_fieldset');
            $fldSet->addField('att_visible_cart', 'select', [
                'name'      => 'ampromorule[att_visible_cart]',
                'label'     => __('Visible In The Cart'),
                'values'    => $this->_yesNoSource->toArray(),
            ],'ampromo_sku');

            $fldSet->addField('att_visible_user_account', 'select', [
                'name'      => 'ampromorule[att_visible_user_account]',
                'label'     => __('Visible In User Account'),
                'values'    => $this->_yesNoSource->toArray(),
            ],'att_visible_cart');
        }

        $salesrule = $this->_coreRegistry->registry('current_promo_sales_rule');

        $ruleId = $salesrule->getId();
        $ampromoRule = $this->_promoRuleFactory;
        $ampromoRule->load($ruleId, 'salesrule_id');

        $salesrule->addData([
            'att_visible_cart' => is_null($ampromoRule->getData('att_visible_cart')) ? 1:$ampromoRule->getData('att_visible_cart'),
            'att_visible_user_account' => is_null($ampromoRule->getData('att_visible_user_account'))? 1:$ampromoRule->getData('att_visible_user_account')
        ]);
    }
}
