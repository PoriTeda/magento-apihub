<?php

namespace Riki\Fraud\Block\Adminhtml\Rule\Edit;

class Form extends \Mirasvit\FraudCheck\Block\Adminhtml\Rule\Edit\Form
{
    /**
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    protected $_templatesFactory;

    /**
     * Form constructor.
     *
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $fieldset
     * @param \Magento\Rule\Block\Conditions $ruleConditions
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateFactory
     */
    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $fieldset,
        \Magento\Rule\Block\Conditions $ruleConditions,
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateFactory
    ) {
        parent::__construct($formFactory, $registry, $fieldset, $ruleConditions, $context);
        $this->_templatesFactory = $templateFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        /** @var \Mirasvit\FraudCheck\Model\Rule $model */
        $model = $this->registry->registry('current_model');

        $form = $this->formFactory->create()->setData([
            'id'      => 'edit_form',
            'action'  => $this->getData('action'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        $form->setFieldNameSuffix('data');
        $this->setForm($form);

        $general = $form->addFieldset('general_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $general->addField('rule_id', 'hidden', [
                'name'  => 'rule_id',
                'value' => $model->getId(),
            ]);
        }

        $general->addField('name', 'text', [
            'label'    => __('Name'),
            'required' => true,
            'name'     => 'name',
            'value'    => $model->getName(),
        ]);

        $general->addField('is_active', 'select', [
            'label'    => __('Is Active'),
            'required' => true,
            'name'     => 'is_active',
            'value'    => $model->getIsActive(),
            'values'   => [0 => __('No'), 1 => __('Yes')],
        ]);

        $general->addField('status', 'select', [
            'label'    => __('Set status to'),
            'required' => true,
            'name'     => 'status',
            'value'    => $model->getStatus(),
            'values'   => [
                'accept' => __('Approve'),
                'review' => __('Review'),
                'reject' => __('Reject')
            ],
        ]);

        $general->addField('duration', 'text', [
            'label'    => __('Accumulated during'),
            'required' => false,
            'name'     => 'duration',
            'value'    => $model->getDuration()
        ]);

        $general->addField('accumulated_type', 'select', [
            //'label'    => __('Accumulated type'),
            'required' => false,
            'name'     => 'accumulated_type',
            'value'    => $model->getAccumulatedType(),
            'values'   => [
                '' => __('Select'),
                'month' => __('Month'),
                'hours' => __('Hours')
            ],
        ]);

        $general->addField('send_email_to', 'text',[
            'label'    => __('Send email to'),
            'required' => false,
            'name'     => 'send_email_to',
            'after_element_html' => '<small>Email address which will receive the email updates. Each Email separated by a semicolon ";"</small>',
            'value'    => $model->getSendEmailTo()
        ]);

        $general->addField('email_template', 'select',[
            'label'    => __('Email template'),
            'required' => false,
            'name'     => 'email_template',
            'value'    => $model->getEmailTemplate(),
            'values'    => $this->emailOption()
        ]);

        $general->addField('warning_message', 'textarea',[
            'label'    => __('Warning message'),
            'required' => false,
            'name'     => 'warning_message',
            'value'    => $model->getWarningMessage()
        ]);

        $renderer = $this->fieldset
            ->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl(
                '*/rule/newConditionHtml/form/rule_conditions_fieldset',
                []
            ));

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            ['legend' => __('Conditions')]
        )->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', [
            'name'     => 'conditions',
            'label'    => __('Rules'),
            'title'    => __('Rules'),
            'required' => true,
        ])->setRule($model)
            ->setRenderer($this->ruleConditions);

        $form->setValues($model->getData());

        return $this;
    }

    protected function emailOption()
    {
        /** @var $collection \Magento\Email\Model\ResourceModel\Template\Collection */
        $collection = $this->_templatesFactory->create();
        $collection->load();
        $options = $collection->toOptionArray();
        array_unshift( $options, ['value' => 0, 'label' => __('Select email template')] );
        return $options;

    }
}
