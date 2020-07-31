<?php

namespace Riki\SubscriptionProfileDisengagement\Block\Adminhtml\Reason\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    protected $disengagementHelper;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $disengagementHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $disengagementHelper,
        array $data = []
    ) {
        $this->disengagementHelper = $disengagementHelper;
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
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->getModel();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Reason Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id', 'value' => $model->getId()]);
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
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Reason Title'),
                'title' => __('Reason Title'),
                'required' => true,
                'value' => $model->getTitle()
            ]
        );

        $fieldset->addField(
            'visibility',
            'select',
            [
                'name' => 'visibility',
                'label' => __('Visibility'),
                'title' => __('Visibility'),
                'required' => true,
                'value' => $model->getVisibility(),
                'options' => $this->disengagementHelper->getVisibilityOptions()
            ]
        );

        $form->setAction($this->getUrl('*/*/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
