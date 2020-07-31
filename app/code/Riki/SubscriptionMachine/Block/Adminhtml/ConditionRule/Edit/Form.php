<?php

namespace Riki\SubscriptionMachine\Block\Adminhtml\ConditionRule\Edit;

use \Riki\SubscriptionMachine\Model\MachineConditionRule;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var MachineConditionRule\Source\SubscriptionCourse
     */
    protected $optionSubscriptionCourse;

    /**
     * @var MachineConditionRule\Source\SubscriptionFrequency
     */
    protected $optionSubscriptionFrequency;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course\Source\Payment
     */
    protected $paymentSource;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $configYesNo;

    /**
     * @var MachineConditionRule
     */
    protected $machineRuleConditionModel;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param MachineConditionRule\Source\SubscriptionCourse $optionSubscriptionCourse
     * @param MachineConditionRule\Source\SubscriptionFrequency $optionSubscriptionFrequency
     * @param \Riki\SubscriptionCourse\Model\Course\Source\Payment $paymentSource
     * @param \Magento\Config\Model\Config\Source\Yesno $configYesNo
     * @param MachineConditionRule $machineRuleConditionModel
     * @param array $data
     */
    public function __construct
    (
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Riki\SubscriptionMachine\Model\MachineConditionRule\Source\SubscriptionCourse $optionSubscriptionCourse,
        \Riki\SubscriptionMachine\Model\MachineConditionRule\Source\SubscriptionFrequency $optionSubscriptionFrequency,
        \Riki\SubscriptionCourse\Model\Course\Source\Payment $paymentSource,
        \Magento\Config\Model\Config\Source\Yesno $configYesNo,
        \Riki\SubscriptionMachine\Model\MachineConditionRule $machineRuleConditionModel,
        array $data = []
    ) {
        $this->optionSubscriptionCourse = $optionSubscriptionCourse;
        $this->optionSubscriptionFrequency = $optionSubscriptionFrequency;
        $this->paymentSource = $paymentSource;
        $this->configYesNo = $configYesNo;
        $this->machineRuleConditionModel = $machineRuleConditionModel;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare edit form
     *
     * @return $this
     */
    protected function _prepareForm()
    {

        $model = $this->_coreRegistry->registry('machinecondition_item');
        $actionParams = ['store' => $model->getStoreId()];
        if ($model->getId()) {
            $actionParams['id'] = $model->getId();
            $model->setCourseCode(json_decode($model->getCourseCode()));
            $model->setFrequency(json_decode($model->getFrequency()));
            $model->setPaymentMethod(json_decode($model->getPaymentMethod()));
        }
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('machine/conditionRule/save', $actionParams),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Free Machine Condition Rule Management')]);
        $this->_addElementTypes($fieldset);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField(
            'machine_code',
            'select',
            [
                'label' => __('Machine type code'),
                'name' => 'machine_code',
                'required' => true,
                'scope' => 'store',
                'values' => $this->machineRuleConditionModel->getMachineCodeOptionArray()
            ]
        );
        $fieldset->addField(
            'course_code',
            'multiselect',
            [
                'label' => __('Subscription course code'),
                'note' => __('Course code - Course name'),
                'name' => 'course_code[]',
                'required' => true,
                'scope' => 'store',
                'values' => $this->optionSubscriptionCourse->getAllOptions()
            ]
        );
        $fieldset->addField(
            'frequency',
            'multiselect',
            [
                'label' => __('Subscription frequency'),
                'name' => 'frequency[]',
                'required' => true,
                'values' => $this->optionSubscriptionFrequency->getAllOptions(),
                'scope' => 'store'
            ]
        );
        $fieldset->addType('category_type', 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category');
        $fieldset->addField(
            'category_id',
            'category_type',
            [
                'label' => __('Categories'),
                'name' => 'category_id',
                'required' => true,
                'scope' => 'store'
            ]
        );
        $fieldset->addField(
            'qty_min',
            'text',
            [
                'label' => __('Minimum purchase quantity'),
                'name' => 'qty_min',
                'required' => true,
                'scope' => 'store'
            ]
        );
        $fieldset->addField(
            'threshold',
            'text',
            [
                'label' => __('Threshold'),
                'name' => 'threshold',
                'scope' => 'store'
            ]
        );
        $fieldset->addField(
            'payment_method',
            'multiselect',
            [
                'label' => __('Payment Method'),
                'name' => 'payment_method[]',
                'scope' => 'store',
                'values' => $this->paymentSource->toOptionArray()
            ]
        );
        $fieldset->addField(
            'wbs',
            'text',
            [
                'name' => 'wbs',
                'id' => 'wbs',
                'label' => __('Wbs'),
                'title' => __('Wbs'),
                'class' => 'validate-wbs-code'
            ]
        );
        $fieldset->addField(
            'sku_specified',
            'select',
            [
                'label' => __('SKU Specified'),
                'name' => 'sku_specified',
                'scope' => 'store',
                'values' => $this->configYesNo->toArray()
            ]
        );

        $this->setForm($form);
        $form->setValues($model->getData());
        $form->setDataObject($model);
        $form->setUseContainer(true);
        return $this;
    }

    /**
     * @return array
     */
    protected function _getValueOfSubCourse()
    {
        \Zend_Debug::dump($this->getData());
        return $this;
    }
}
