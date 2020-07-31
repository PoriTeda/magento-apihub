<?php

namespace Riki\Rma\Block\Adminhtml\Reason\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Riki\Rma\Model\Config\Source\Reason\Dueto
     */
    protected $dueTo;

    /**
     * Form constructor.
     * @param \Riki\Rma\Model\Config\Source\Reason\Dueto $dueTo
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Model\Config\Source\Reason\Dueto $dueTo,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->dueTo = $dueTo;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Retrieve template object
     *
     * @return \Magento\Newsletter\Model\Template
     */
    public function getModel()
    {
        return $this->_coreRegistry->registry('_current_reason');
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Backend\Block\Widget\Form\Generic
     */
    protected function _prepareForm()
    {
        $model = $this->getModel();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post'
            ]
        ]);

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => __('Reason Information'),
                'class' => 'fieldset-wide'
            ]
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', [
                'name' => 'id',
                'value' => $model->getId()
            ]);
        }

        $fieldset->addField(
            'code',
            'text',
            [
                'name' => 'code',
                'label' => __('Reason Code'),
                'title' => __('Reason Code'),
                'class' => 'validate-number',
                'required' => true,
                'value' => $model->getCode()
            ]
        );

        $fieldset->addField(
            'description_en',
            'text',
            [
                'name' => 'description_en',
                'label' => __('Reason Description (EN)'),
                'title' => __('Reason Description (EN)'),
                'value' => $model->getDescriptionEn()
            ]
        );

        $fieldset->addField(
            'description_jp',
            'text',
            [
                'name' => 'description_jp',
                'label' => __('Reason Description (JP)'),
                'title' => __('Reason Description (JP)'),
                'value' => $model->getDescriptionJp()
            ]
        );

        $fieldset->addField(
            'due_to',
            'select',
            [
                'label' => __('Due To'),
                'title' => __('Due To'),
                'name' => 'due_to',
                'required' => true,
                'value' => $model->getDueTo(),
                'options' => ['' => ' '] + $this->dueTo->toArray()
            ]
        );

        $fieldset->addField(
            'sap_code',
            'text',
            [
                'label' => __('SAP Code'),
                'title' => __('SAP Code'),
                'name' => 'sap_code',
                'value' => $model->getSapCode()
            ]
        );

        $form->setAction($this->getUrl('*/*/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
