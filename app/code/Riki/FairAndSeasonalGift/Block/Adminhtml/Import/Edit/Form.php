<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Import\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Prepare form
     *
     * @return $this
     */
    protected $_modelFactory;

    public function __construct(\Magento\Backend\Block\Template\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Data\FormFactory $formFactory, array $data,\Riki\SerialCode\Model\SerialCodeFactory $SerialCodeFactory)
    {
        $this->_modelFactory = $SerialCodeFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getUrl('*/*/validate'), 'method' => 'post','enctype'=>'multipart/form-data']]
        );
        $form->setUseContainer(true);
        $isElementDisabled = false;
        $form->setHtmlIdPrefix('FairAndSeasonalGift_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Fair Seasonal Gift CSV file importing form')]);
        $fieldset->addField(
            'csv_file_mng',
            'file',
            [
                'name' => 'csv_file_mng',
                'label' => __('Select Management File to Import'),
                'title' => __('Select Management File to Import'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'csv_file_recom',
            'file',
            [
                'name' => 'csv_file_recom',
                'label' => __('Select Recommendation File to Import'),
                'title' => __('Select Recommendation File to Import'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'csv_file_conn',
            'file',
            [
                'name' => 'csv_file_conn',
                'label' => __('Select Connection File to Import'),
                'title' => __('Select Connection File to Import'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'csv_file_detail',
            'file',
            [
                'name' => 'csv_file_detail',
                'label' => __('Select Details File to Import'),
                'title' => __('Select Details File to Import'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
