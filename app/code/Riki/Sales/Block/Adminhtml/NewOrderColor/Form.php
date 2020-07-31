<?php

namespace Riki\Sales\Block\Adminhtml\NewOrderColor;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Status\NewStatus\Form
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
            ['color_code' => 'label', 'label' => __('Status Color Code'), 'class' => 'required-entry validate-color-code', 'required' => true]
        );
        if ($model) {
            $form->addValues($model->getData());
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

}
