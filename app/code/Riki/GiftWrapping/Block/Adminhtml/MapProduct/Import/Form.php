<?php

namespace Riki\GiftWrapping\Block\Adminhtml\MapProduct\Import;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {

        parent::_construct();
        $this->setId('giftwrapping_mapproduct_import');
        $this->setTitle(__('Import'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post','enctype'=>'multipart/form-data']]
        );
        $form->setHtmlIdPrefix('giftwrapping_import_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'csv_import_giftwrapping',
            'file',
            [
                'name' => 'csv_import_giftwrapping',
                'label' => __('Upload CSV GiftWrapping file'),
                'title' => __('Upload CSV GiftWrapping file'),
                'required' => true,
                'disabled' => false,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
