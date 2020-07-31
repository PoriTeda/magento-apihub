<?php
namespace Riki\SalesRule\Block\Adminhtml\Import\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{


    public function __construct(\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry, \Magento\Framework\Data\FormFactory
        $formFactory, array $data)
    {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getUrl('*/*/import_validate'), 'method' => 'post','enctype'=>'multipart/form-data']]
        );
        $form->setUseContainer(true);
        $isElementDisabled = false;
        $form->setHtmlIdPrefix('RikiSalesRule_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Riki Sales Rule Coupon CSV file importing form')]);
        $fieldset->addField(
            'csv_file_coupon_content',
            'file',
            [
                'name' => 'csv_file_coupon_content',
                'label' => __('Select Coupon Content File to Import'),
                'title' => __('Select Coupon Content File to Import'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'csv_file_coupon_product',
            'file',
            [
                'name' => 'csv_file_coupon_product',
                'label' => __('Select Coupon Product File to Import'),
                'title' => __('Select Coupon Product File to Import'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
