<?php
//namespace Riki\CatalogRule\Block\Adminhtml\Promo\Catalog\Edit\Tab;
//
//class Main extends \Magento\CatalogRule\Block\Adminhtml\Promo\Catalog\Edit\Tab\Main
//{
//    /**
//     * @var \Riki\SubscriptionCourse\Model\Course
//     */
//    protected $_course;
//    /**
//     * @var \Riki\CatalogRule\Model\Rule\SubscriptionDeliveryOptionsProvider
//     */
//    protected $_subscriptionDeliveryOptionsProvider;
//    /**
//     * @var \Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory
//     */
//    protected $_fieldFactory;
//
//    /**
//     * Main constructor.
//     * @param \Riki\CatalogRule\Model\Rule\SubscriptionDeliveryOptionsProvider $subscriptionDeliveryOptionsProvider
//     * @param \Riki\SubscriptionCourse\Model\Course $course
//     * @param \Magento\Backend\Block\Template\Context $context
//     * @param \Magento\Framework\Registry $registry
//     * @param \Magento\Framework\Data\FormFactory $formFactory
//     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
//     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
//     * @param \Magento\Framework\Convert\DataObject $objectConverter
//     * @param \Magento\Store\Model\System\Store $systemStore
//     * @param array $data
//     */
//    public function __construct(
//        \Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory $fieldFactory,
//        \Riki\CatalogRule\Model\Rule\SubscriptionDeliveryOptionsProvider $subscriptionDeliveryOptionsProvider,
//        \Riki\SubscriptionCourse\Model\Course $course,
//        \Magento\Backend\Block\Template\Context $context,
//        \Magento\Framework\Registry $registry,
//        \Magento\Framework\Data\FormFactory $formFactory,
//        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
//        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
//        \Magento\Framework\Convert\DataObject $objectConverter,
//        \Magento\Store\Model\System\Store $systemStore,
//        array $data = []
//    )
//    {
//        $this->_fieldFactory = $fieldFactory;
//        $this->_subscriptionDeliveryOptionsProvider = $subscriptionDeliveryOptionsProvider;
//        $this->_course = $course;
//        parent::__construct($context, $registry, $formFactory, $groupRepository, $searchCriteriaBuilder, $objectConverter, $systemStore, $data);
//    }
//
//    protected function _prepareForm()
//    {
//        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
//
//        /** @var \Magento\Framework\Data\Form $form */
//        $form = $this->_formFactory->create();
//        $form->setHtmlIdPrefix('rule_');
//
//        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);
//
//        if ($model->getId()) {
//            $fieldset->addField('rule_id', 'hidden', ['name' => 'rule_id']);
//        }
//
//        $fieldset->addField(
//            'name',
//            'text',
//            [
//                'name' => 'name',
//                'label' => __('Rule Name'),
//                'title' => __('Rule Name'),
//                'required' => true,
//                'maxlength' => '2047'
//            ]
//        );
//
//        $fieldset->addField(
//            'description',
//            'textarea',
//            [
//                'name' => 'description',
//                'label' => __('Description'),
//                'title' => __('Description'),
//                'style' => 'height: 100px;'
//            ]
//        );
//
//        $fieldset->addField(
//            'is_active',
//            'select',
//            [
//                'label' => __('Status'),
//                'title' => __('Status'),
//                'name' => 'is_active',
//                'required' => true,
//                'options' => ['1' => __('Active'), '0' => __('Inactive')]
//            ]
//        );
//
//        if ($this->_storeManager->isSingleStoreMode()) {
//            $websiteId = $this->_storeManager->getStore(true)->getWebsiteId();
//            $fieldset->addField('website_ids', 'hidden', ['name' => 'website_ids[]', 'value' => $websiteId]);
//            $model->setWebsiteIds($websiteId);
//        } else {
//            $field = $fieldset->addField(
//                'website_ids',
//                'multiselect',
//                [
//                    'name' => 'website_ids[]',
//                    'label' => __('Websites'),
//                    'title' => __('Websites'),
//                    'required' => true,
//                    'values' => $this->_systemStore->getWebsiteValuesForForm()
//                ]
//            );
//            $renderer = $this->getLayout()->createBlock(
//                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
//            );
//            $field->setRenderer($renderer);
//        }
//
//        $customerGroups = $this->_groupRepository->getList($this->_searchCriteriaBuilder->create())->getItems();
//        $fieldset->addField(
//            'customer_group_ids',
//            'multiselect',
//            [
//                'name' => 'customer_group_ids[]',
//                'label' => __('Customer Groups'),
//                'title' => __('Customer Groups'),
//                'required' => true,
//                'values' => $this->_objectConverter->toOptionArray($customerGroups, 'id', 'code')
//            ]
//        );
//
//        $dateFormat = $this->_localeDate->getDateFormat(
//            \IntlDateFormatter::SHORT
//        );
//
//        $timeFormat = $this->_localeDate->getTimeFormat(
//            \IntlDateFormatter::SHORT
//        );
//
//        $fieldset->addField(
//            'from_date',
//            'date',
//            [
//                'name' => 'from_date',
//                'label' => __('From'),
//                'title' => __('From'),
//                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
//                'date_format' => $dateFormat
//            ]
//        );
//
//        $fieldset->addField(
//            'from_time',
//            'time',
//            [
//                'name' => 'from_time',
//                'label' => __('From time'),
//                'title' => __('From time'),
//                'input_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
//                'time_format' => $timeFormat
//            ]
//        );
//
//        $fieldset->addField(
//            'to_date',
//            'date',
//            [
//                'name' => 'to_date',
//                'label' => __('To'),
//                'title' => __('To'),
//                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
//                'date_format' => $dateFormat
//            ]
//        );
//
//        $fieldset->addField(
//            'to_time',
//            'time',
//            [
//                'name' => 'to_time',
//                'label' => __('To time'),
//                'title' => __('To time'),
//                'input_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
//                'time_format' => $timeFormat
//            ]
//        );
//
//        $fieldset->addField('sort_order', 'text', ['name' => 'sort_order', 'label' => __('Priority')]);
//
//        $subscriptionField = $fieldset->addField('subscription', 'select', [
//            'name' => 'subscription',
//            'label' => __('SPOT/Subscription'),
//            'options' => [
//                '' => 'Please select',
//                \Riki\CatalogRule\Model\Rule::APPLY_SPOT_ONLY => __('SPOT only'),
//                \Riki\CatalogRule\Model\Rule::APPLY_SUBSCRIPTION_ONLY => __('Subscription only'),
//                \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION => __('SPOT and subscription')
//            ],
//            'onclick' => 'handleSubscriptionDelivery()',
//            'required' => true
//        ]);
//
//        $subscriptionDependField = $this->_fieldFactory->create([
//            'fieldData' => [
//                'separator' => ',',
//                'value' => implode(',', [\Riki\CatalogRule\Model\Rule::APPLY_SUBSCRIPTION_ONLY,\Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION])
//            ],
//            'fieldPrefix' => ''
//        ]);
//
//        $applySubscriptionField = $fieldset->addField('apply_subscription', 'multiselect', [
//            'name' => 'apply_subscription',
//            'label' => __('Apply only to subscription'),
//            'values' => $this->_course->getCoursesForForm(),
//            'required' => true,
//            'onclick' => 'getFrequencyByCourse(this)'
//        ]);
//
//        $applyFrequencyField = $fieldset->addField('apply_frequency', 'multiselect', [
//            'name' => 'apply_frequency',
//            'label' => __('Apply only to frequency'),
//            'values' => $this->_course->getFrequencyValuesForForm(),
//            'required' => true
//        ]);
//
//        $fieldset->addField('course_frequency', 'hidden', [
//            'name' => 'course_frequency',
//            'id' => 'course_frequency'
//        ]);
//        $model->setCourseFrequency(\Zend_Json::encode($this->_course->getCourseFrequencyList()));
//
//        $subscriptionDeliveryField = $fieldset->addField('subscription_delivery', 'select', [
//            'name' => 'subscription_delivery',
//            'label' => __('Subscription deliveries'),
//            'options' => $this->_subscriptionDeliveryOptionsProvider->toArray(),
//            'onchange' => 'handleSubscriptionDelivery()'
//        ]);
//
//        $deliveryNField = $fieldset->addField('delivery_n', 'text', [
//            'name' => 'delivery_n',
//            'label' => __('Delivery N'),
//            'class' => 'validate-number',
//            'required' => true,
//            'disabled' => true
//        ]);
//
//        $form->setValues($model->getData());
//
//        if ($model->isReadonly()) {
//            foreach ($fieldset->getElements() as $element) {
//                $element->setReadonly(true, true);
//            }
//        }
//
//        $this->setForm($form);
//
//        $dep = $this->getLayout()->createBlock('Magento\SalesRule\Block\Widget\Form\Element\Dependence');
//        $dep->addFieldMap($subscriptionField->getHtmlId(), $subscriptionField->getName())
//            ->addFieldMap($applySubscriptionField->getHtmlId(), $applySubscriptionField->getName())
//            ->addFieldMap($applyFrequencyField->getHtmlId(), $applyFrequencyField->getName())
//            ->addFieldMap($subscriptionDeliveryField->getHtmlId(), $subscriptionDeliveryField->getName())
//            ->addFieldMap($deliveryNField->getHtmlId(), $deliveryNField->getName())
//            ->addFieldDependence($applySubscriptionField->getName(), $subscriptionField->getName(), $subscriptionDependField)
//            ->addFieldDependence($applyFrequencyField->getName(), $subscriptionField->getName(), $subscriptionDependField)
//            ->addFieldDependence($subscriptionDeliveryField->getName(), $subscriptionField->getName(), $subscriptionDependField);
//        $this->setChild('form_after', $dep);
//
//        $this->_eventManager->dispatch('adminhtml_promo_catalog_edit_tab_main_prepare_form', ['form' => $form]);
//
//        return $this;
//    }
//}