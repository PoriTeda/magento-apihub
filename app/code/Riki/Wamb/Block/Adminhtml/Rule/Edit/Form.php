<?php

namespace Riki\Wamb\Block\Adminhtml\Rule\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{
    /**
     * @var \Riki\Wamb\Model\Config\Source\IsActive
     */
    protected $isActiveSource;

    /**
     * @var \Riki\Wamb\Model\Config\Source\CourseIds
     */
    protected $courseIdsSource;

    /**
     * Form constructor.
     *
     * @param \Riki\Wamb\Model\Config\Source\IsActive $isActiveSource
     * @param \Riki\Wamb\Model\Config\Source\CourseIds $courseIdsSource
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Riki\Wamb\Model\Config\Source\IsActive $isActiveSource,
        \Riki\Wamb\Model\Config\Source\CourseIds $courseIdsSource,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->courseIdsSource = $courseIdsSource;
        $this->isActiveSource = $isActiveSource;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('rule_form');
        $this->setTitle(__('Rule Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Riki\Wamb\Model\Rule $model */
        $model = $this->_coreRegistry->registry('current_wamb_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            ]
        ]);

        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => __('Rule General Information'),
            'class' => 'fieldset-wide'
        ]);

        if ($model->getRuleId()) {
            $fieldset->addField('rule_id', 'hidden', ['name' => 'rule_id']);
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'label' => __('WAMB Rule Name'),
                'title' => __('Rule Name'),
                'name' => 'name',
                'class' => 'required-entry',
                'required' => true,
                'maxlength' => 255,
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Active'),
                'title' => __('Active'),
                'name' => 'is_active',
                'options' => $this->isActiveSource->toArray(),
            ]
        );
        $fieldset->addField(
            'course_ids',
            'multiselect',
            [
                'label' => __('Subscription course code'),
                'note' => __('Course code - Course name'),
                'name' => 'course_ids[]',
                'required' => true,
                'scope' => 'store',
                'values' => $this->courseIdsSource->toOptionArray()
            ]
        );

        $fieldset->addType('category_type', 'Riki\Wamb\Block\Adminhtml\Product\Helper\Form\Category');

        $fieldset->addField(
            'category_ids',
            'category_type',
            [
                'name' => 'category_ids',
                'required' => true,
                'class' => 'field-category_ids',
                'id' => 'category_id',
                'label' => __('Categories'),
                'title' => __('Categories'),
                'scope' => 'store',
                'values' => $model->getCategoryIds()
            ]
        );

        $fieldset->addField(
            'min_purchase_qty',
            'text',
            [
                'label' => __('Minimum purchase quantity'),
                'title' => __('Minimum purchase quantity'),
                'name' => 'min_purchase_qty',
                'class' => 'validate-digits validate-greater-than-zero ',
                'required' => true,

            ]
        );

        $this->setForm($form);
        $form->setValues($model->getData());
        $form->setDataObject($model);
        $form->setUseContainer(true);

        return parent::_prepareForm();
    }
}