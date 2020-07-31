<?php

namespace Riki\SerialCode\Block\Adminhtml\SerialCode\Import;

/**
 * Adminhtml serial code edit form
 */
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
        $this->setId('import_serial_code_form');
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
        $data['import_serial_Code'] = 1;
        $form->setHtmlIdPrefix('serial_code_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'csv_file',
            'file',
            [
                'name' => 'csv_file',
                'label' => __('Upload Csv file'),
                'title' => __('Upload Csv file'),
                'required' => true,
                'disabled' => false,
            ]
        );
        $fieldset->addField('import_serial_Code', 'hidden', [
            'name' => 'serial_import',
            'value' => '1'
        ]);
        $form->addValues(array('import_serial_Code'=> '1'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
