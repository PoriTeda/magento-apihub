<?php

namespace Riki\Sales\Block\Adminhtml\OrderColor;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Status\Edit\Form
{


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('new_order_status');
    }

    protected function _prepareForm()
    {

        $model = $this->_coreRegistry->registry('current_status');
        parent::_prepareForm();

        $form = $this->getForm();
        $form->getElement('base_fieldset')->addField(
            'color_code',
            'text',
            ['name' => 'color_code', 'label' => __('Status Color Code'), 'class' => 'required-entry validate-length maximum-length-7 validate-color-code','note' =>'A valid Color code must be format by # and limit 7 characters. Example: #00FF33 or #0F3.' ,'required' => true]
        );
        if ($model) {
            $form->addValues($model->getData());
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

}
