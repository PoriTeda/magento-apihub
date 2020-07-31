<?php
namespace Riki\Fraud\Block\Adminhtml\Import\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Prepare form
     *
     * @return $this
     */
    protected $_modelFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Mirasvit\FraudCheck\Model\RuleFactory $ruleFraudFactory,
        array $data = []
    ) {
        $this->_modelFactory = $ruleFraudFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getUrl('*/*/validate'), 'method' => 'post','enctype'=>'multipart/form-data']]
        );
        $form->setUseContainer(true);
        $isElementDisabled = false;
        $form->setHtmlIdPrefix('fraud_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Blacklist Importing Form')]);
        $fieldset->addField(
            'csv_file',
            'file',
            [
                'name' => 'yml_file',
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
