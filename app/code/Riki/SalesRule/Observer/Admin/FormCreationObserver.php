<?php
namespace Riki\SalesRule\Observer\Admin;

use Magento\Framework\Event\ObserverInterface;
use Magento\OfflineShipping\Model\SalesRule\Rule;

class FormCreationObserver implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $fieldset = $observer->getForm()->getElement('actions_fieldset');

        $fieldset->addField(
            'type_by',
            'select',
            [
                'name' => 'type_by',
                'label' => __('Reward Point Type'),
                'title' => __('Reward Point Type'),
                'values' => [
                    'riki_type_fixed' => __('Fixed reward point'),
                    'riki_type_percent' => __('Percent reward point')
                ]
            ],
            'stop_rules_processing'
        );

        $fieldset->addField(
            'points_delta',
            'text',
            [
                'name' => 'points_delta',
                'label' => __('Add Reward Points'),
                'title' => __('Add Reward Points'),
                'class' => 'validate-number'
            ],
            'type_by'
        );
        $fieldset->addField(
            'point_expiration_period',
            'text',
            [
                'name' => 'point_expiration_period',
                'label' => __('Point expiration period (in days)'),
                'title' => __('Point expiration period'),
                'class' => 'validate-number validate-not-negative-number'
            ],
            'type_by'
        );

        $fieldset->addField(
            'free_cod_charge',
            'select',
            [
                'name' => 'free_cod_charge',
                'label' => __('Free payment fee'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes')
                ],
            ],
            'simple_free_shipping'
        );

        // RIKI-2037 - replace wbs by 4 fields wbs below
        /*$fieldset->addField(
            'wbs',
            'text',
            [
                'name' => 'wbs',
                'label' => __('WBS'),
                'required' => true,
                'class' => 'validate-wbs-code'
            ],
            'discount_step'
        );*/

        $fieldset->addField(
            'wbs_promo_item_free_gift',
            'text',
            [
                'name' => 'wbs_promo_item_free_gift',
                'label' => __('Promo item / Free gift WBS code'),
                'required' => true,
                'class' => 'validate-wbs-code'
            ],
            'discount_step'
        );

        $fieldset->addField(
            'wbs_shopping_point',
            'text',
            [
                'name' => 'wbs_shopping_point',
                'label' => __('Shopping point WBS'),
                'required' => true,
                'class' => 'validate-wbs-code'
            ],
            'discount_step'
        );

        $fieldset->addField(
            'wbs_free_delivery',
            'text',
            [
                'name' => 'wbs_free_delivery',
                'label' => __('Free delivery WBS code'),
                'required' => true,
                'class' => 'validate-wbs-code'
            ],
            'discount_step'
        );

        $fieldset->addField(
            'wbs_free_payment_fee',
            'text',
            [
                'name' => 'wbs_free_payment_fee',
                'label' => __('Free payment fee WBS code'),
                'class' => 'validate-wbs-code'
            ],
            'discount_step'
        );

        $fieldset->addField(
            'account_code',
            'text',
            [
                'name' => 'account_code',
                'label' => __('Account code'),
                'class' => 'validate-number'
            ],
            'wbs'
        );

        $fieldset->addField(
            'sap_condition_type',
            'text',
            [
                'name' => 'sap_condition_type',
                'label' => __('SAP Condition Type')
            ],
            'account_code'
        );

        // related to ticket RIKI-4479
        $fieldset->removeField('simple_free_shipping');
        $fieldset->addField(
            'simple_free_shipping',
            'select',
            [
                'label' => __('Free Shipping'),
                'title' => __('Free Shipping'),
                'name' => 'simple_free_shipping',
                'options' => [
                    0 => __('No'),
                    Rule::FREE_SHIPPING_ITEM => __('For matching items only')
                ]
            ],
            'points_delta'
        );
    }
}