<?php

namespace Riki\Questionnaire\Block\Adminhtml\Questions\Import;

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
        $this->setId('import_questionaire_form');
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
        $form->setHtmlIdPrefix('questionaire_code_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'csv_file_enquete',
            'file',
            [
                'name' => 'csv_file_enquete',
                'label' => __('Upload Csv Enquete file'),
                'title' => __('Upload Csv Enquete file'),
                'required' => true,
                'disabled' => false,
            ]
        );
        $fieldset->addField(
            'csv_file_question',
            'file',
            [
                'name' => 'csv_file_question',
                'label' => __('Upload Csv Enquete question file'),
                'title' => __('Upload Csv Enquete question file'),
                'required' => true,
                'disabled' => false,
            ]
        );
        $fieldset->addField(
            'csv_file_question_choice',
            'file',
            [
                'name' => 'csv_file_question_choice',
                'label' => __('Upload Csv Question choice file'),
                'title' => __('Upload Csv Question choice file'),
                'required' => true,
                'disabled' => false,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
