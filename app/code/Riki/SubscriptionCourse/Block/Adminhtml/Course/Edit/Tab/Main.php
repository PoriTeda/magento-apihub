<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_systemStore;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfigModel;

    /**
     * WYSIWYG config data
     *
     * @var \Magento\Framework\DataObject
     */
    protected $_wysiwygConfig;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    )
    {
        $this->_systemStore = $systemStore;
        $this->_wysiwygConfigModel = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Riki\SubscriptionCourse\Model\Course $model */
        $model = $this->_coreRegistry->registry('subscription_course');

        $isElementDisabled = false;

        $isLastOrderTimeIsDelayPaymentDisable = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('cou_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $fieldset->addField('course_id', 'hidden', ['name' => 'course_id']);
            $isElementDisabled = true;
            $isLastOrderTimeIsDelayPaymentDisable = true;
        } else {
            $fieldset->addField('subscription_type', 'hidden', ['name' => 'subscription_type']);
            $fieldset->addField('hanpukai_type', 'hidden', ['name' => 'hanpukai_type']);
        }

        $fieldset->addField(
            'course_code',
            'text',
            [
                'name' => 'course_code',
                'id' => 'course_code',
                'label' => __('Course Code'),
                'title' => __('Course Code'),
                'class' => 'required-entry',
                'required' => true,
                'maxlength' => 20,
            ]
        );

        $fieldset->addField(
            'course_name',
            'text',
            [
                'name' => 'course_name',
                'id' => 'course_name',
                'label' => __('Course Name'),
                'title' => __('Course Name'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'navigation_path',
            'textarea',
            [
                'name' => 'navigation_path',
                'id' => 'navigation_path',
                'label' => __('Navigation Path'),
                'title' => __('Navigation Path'),
            ]
        );

        $fieldset->addField(
            'description',
            'editor',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
                'config' => $this->_getWysiwygConfig(),
                'wysiwyg' => true,
            ]
        );
        $fieldset->addField(
            'is_enable',
            'select',
            [
                'label' => __('Active'),
                'title' => __('Active'),
                'name' => 'is_enable',
                'required' => true,
                'values' => $model->getAvailableStatuses(),
            ]
        );
        if (!$model->getId()) {
            $model->setData('is_enable', '1');
        }

        $dateFormat = 'yyyy/M/d';
        if ($model->hasData('launch_date')) {
            $datetime = new \DateTime($model->getData('launch_date'));
            $model->setData('launch_date', $datetime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone())));
        }

        $fieldset->addField(
            'launch_date',
            'date',
            [
                'name' => 'launch_date',
                'label' => __('Launch date'),
                'title' => __('Launch date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
                'date_format' => $dateFormat,
                'required' => true,
                'readonly' => 'readonly'
            ]
        );

        if ($model->hasData('close_date') && $model->getData('close_date')) {
            $datetime = new \DateTime($model->getData('close_date'));
            $model->setData('close_date', $datetime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone())));
        }

        $fieldset->addField(
            'close_date',
            'date',
            [
                'name' => 'close_date',
                'label' => __('Close date'),
                'title' => __('Close date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
                'date_format' => $dateFormat,
                'readonly' => 'readonly'
            ]
        );

        $fieldset->addField(
            'frequency_ids',
            $model->getSubscriptionType() != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI ? 'multiselect' : 'select',
            [
                'name' => 'frequency_ids[]',
                'id' => 'frequency',
                'label' => __('Frequency option'),
                'title' => __('Frequency option'),
                'class' => 'validate-hanpukai-if-any',
                'values' => $model->getFrequencyValuesForForm($model->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'next_delivery_date_calculation_option',
            'select',
            [
                'name' => 'next_delivery_date_calculation_option',
                'id' => 'next_delivery_date_calculation_option',
                'label' => __('Next Delivery Date Calculation Option'),
                'title' => __('Next Delivery Date Calculation Option'),
                'options' => $model->getNextDeliveryDateCalculationOption(),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'duration_unit',
            'select',
            [
                'name' => 'duration_unit',
                'id' => 'duration_unit',
                'label' => __('Duration Unit'),
                'title' => __('Duration Unit'),
                'options' => $model->getDurationUnits(),
            ]
        );

        $fieldset->addField(
            'exclude_buffer_days',
            'select',
            [
                'name' => 'exclude_buffer_days',
                'id' => 'exclude_buffer_days',
                'label' => __('Exclude buffer days'),
                'title' => __('Exclude buffer days'),
                'options' => $model->getExcludeBufferDaysOptions(),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'duration_interval',
            'text',
            [
                'name' => 'duration_interval',
                'id' => 'duration_interval',
                'label' => __('Duration interval (in days)'),
                'title' => __('Duration interval (in days)'),
                'class' => 'validate-number',
            ]
        );

        $fieldset->addField(
            'must_select_sku',
            'text',
            [
                'name' => 'must_select_sku',
                'id' => 'must_select_sku',
                'label' => __('Must Select SKU'),
                'title' => __('Must Select SKU'),
                'required' => false,
                'note' => __('Input category ID and related quantity (pattern: category_id:qty)'),
            ]
        );

        $fieldset->addField(
            'minimum_order_qty',
            'text',
            [
                'name' => 'minimum_order_qty',
                'id' => 'minimum_order_qty',
                'label' => __('Minimum order Qty'),
                'title' => __('Minimum order Qty'),
                'class' => 'validate-number',
                'required' => false,
            ]
        );

        if ($model->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_SUBSCRIPTION
            || $model->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES
        ) {
            $fieldset->addField(
                'is_delay_payment',
                'select',
                [
                    'label' => __('Is delay payment'),
                    'title' => __('Is delay payment'),
                    'name' => 'is_delay_payment',
                    'required' => true,
                    'disabled' => $isElementDisabled,
                    'values' => $model->getYesNo()
                ]
            );

            $fieldset->addField(
                'captured_amount_calculation_option',
                'select',
                [
                    'label' => __('Captured amount calculation option'),
                    'title' => __('Captured amount calculation option'),
                    'name' => 'captured_amount_calculation_option',
                    'required' => false,
                    'values' => $model->getOptionCaptureAmount()
                ]
            );

            $fieldset->addField(
                'is_shopping_point_deduction',
                'select',
                [
                    'label' => __('Shopping point deduction for 1st delivery'),
                    'title' => __('Is delay payment'),
                    'name' => 'is_shopping_point_deduction',
                    'required' => false,
                    'disabled' => $isElementDisabled,
                    'values' => $model->getYesNo()
                ]
            );

            $fieldset->addField(
                'payment_delay_time',
                'text',
                [
                    'name' => 'payment_delay_time',
                    'id' => 'payment_delay_time',
                    'label' => __('Payment delay time (days)'),
                    'title' => __('Payment delay time (days)'),
                    'class' => 'validate-zero-or-greater integer',
                    'required' => true,
                    'disabled' => $isElementDisabled,
                    'note' => __('Define when order will be captured for delay payment orders'),
                ]
            );
            $fieldset->addField(
                'is_auto_box',
                'select',
                [
                    'name' => 'is_auto_box',
                    'id' => 'is_auto_box',
                    'label' => __('Auto Box'),
                    'title' => __('Auto Box'),
                    'required' => true,
                    'values' => $model->getYesNo(),
                    'disabled' => $isElementDisabled
                ]
            );
            $fieldset->addField(
                'last_order_time_is_delay_payment',
                'text',
                [
                    'name' => 'last_order_time_is_delay_payment',
                    'id' => 'last_order_time_is_delay_payment',
                    'label' => __('Last Order Time Is Delay Payment'),
                    'title' => __('Last Order Time Is Delay Payment'),
                    'class' => 'validate-greater-than-zero',
                    'disabled' => $isLastOrderTimeIsDelayPaymentDisable
                ]
            );
        }

        $fieldset->addField(
            'penalty_fee',
            'text',
            [
                'name' => 'penalty_fee',
                'id' => 'penalty_fee',
                'label' => __('Penalty fee'),
                'title' => __('Penalty fee'),
                'class' => 'validate-number',
                'required' => false,
            ]
        );

        $fieldset->addField(
            'visibility',
            'select',
            [
                'name' => 'visibility',
                'id' => 'visibility',
                'label' => __('Visibility'),
                'title' => __('Visibility'),
                'values' => $model->getVisibility(),
                'required' => false,
            ]
        );


        $fieldset->addField(
            'design',
            'select',
            [
                'name' => 'design',
                'id' => 'design',
                'label' => __('Design'),
                'title' => __('Design'),
                'values' => $model->getDesign(),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'nth_delivery_simulation',
            'text',
            [
                'name' => 'nth_delivery_simulation',
                'id' => 'nth_delivery_simulation',
                'label' => __('Nth Delivery Simulation'),
                'title' => __('Nth Delivery Simulation'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'terms_of_use',
            '\Riki\SubscriptionCourse\Block\Adminhtml\Form\Element\File',
            [
                'name' => 'terms_of_use',
                'id' => 'terms_of_use',
                'label' => __('Terms of use'),
                'title' => __('Terms of use'),
                'required' => false,
            ]
        );

        if (!$model->getId()) {
            $model->setData('visibility', '3');
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Basic Information');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Basic Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action.
     *
     * @param string $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get Wysiwyg Config
     *
     * @return \Magento\Framework\DataObject
     */
    protected function _getWysiwygConfig()
    {
        if ($this->_wysiwygConfig === null) {
            $this->_wysiwygConfig = $this->_wysiwygConfigModel->getConfig(
                ['tab_id' => $this->getTabId(), 'skip_widgets' => ['Magento\Banner\Block\Widget\Banner']]
            );
        }
        return $this->_wysiwygConfig;
    }
}
