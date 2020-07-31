<?php

namespace Riki\User\Block\Adminhtml\Password\Import;

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
        $this->setId('password_import');
        $this->setTitle(__('Import'));
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getUrl('*/*/validate'), 'method' => 'post', 'enctype' => 'multipart/form-data']]
        );
        $form->setHtmlIdPrefix('password_import_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'csv_import_password',
            'file',
            [
                'name' => 'csv_import_password',
                'label' => __('Upload CSV Password Dictionary file'),
                'title' => __('Upload CSV Password Dictionary  file'),
                'required' => true,
                'disabled' => false,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
