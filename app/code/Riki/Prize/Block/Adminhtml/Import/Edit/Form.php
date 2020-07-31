<?php
namespace Riki\Prize\Block\Adminhtml\Import\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Prepare form
     *
     * @return $this
     */
    protected $_modelFactory;

    public function __construct(\Magento\Backend\Block\Template\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Data\FormFactory $formFactory, array $data,\Riki\Prize\Model\PrizeFactory $prizeFactory)
    {
        $this->_modelFactory = $prizeFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getUrl('*/*/validate'), 'method' => 'post','enctype'=>'multipart/form-data']]
        );
        $form->setUseContainer(true);
        $isElementDisabled = false;
        $form->setHtmlIdPrefix('prize_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Prize Csv file Importing Form')]);
        $fieldset->addField(
            'csv_file',
            'file',
            [
                'name' => 'csv_file',
                'label' => __('Select File to Import'),
                'title' => __('Select File to Import'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
