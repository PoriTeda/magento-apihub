<?php

namespace Bluecom\PaymentFee\Block\Adminhtml\Payment\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('payment_fee');

        /**
         * Form
         *
         * @var \Magento\Framework\Data\Form $form
         */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ],
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Payment Fee Information')]);

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }

        $fieldset->addField(
            'payment_name',
            'text',
            [
                'name' => 'payment_name',
                'label' => __('Payment Method'),
                'title' => __('Payment Method'),
                'required' => true,
                'disabled' => true
            ]
        );
        $fieldset->addField(
            'fixed_amount',
            'text',
            [
                'name' => 'fixed_amount',
                'label' => __('Fixed Amount (Including Tax)'),
                'title' => __('Fixed Amount (Including Tax)'),
                'required' => true,
                'class' => 'validate-number validate-zero-or-greater'
            ]
        );
        $fieldset->addField(
            'active',
            'select',
            [
                'label' => __('Active'),
                'title' => __('Active'),
                'name' => 'active',
                'note' => __('The payment fee will be applied on your payment method when it is activated.'),
                'required' => true,
                'options' => \Bluecom\PaymentFee\Model\PaymentFee::getActive(),
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}