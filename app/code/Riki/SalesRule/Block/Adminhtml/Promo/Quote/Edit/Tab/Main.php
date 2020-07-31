<?php
namespace Riki\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject as ObjectConverter;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\System\Store;
use Riki\SubscriptionCourse\Model\Course;
use Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory;
use Magento\Framework\Json\Helper\Data as JsonHelperData;

class Main
{
    /**
     * Course
     *
     * @var \Riki\SubscriptionCourse\Model\Course $_course Course
     */
    protected $_course;

    /**
     * @var JsonHelperData $_jsonHelper Data
     */
    protected $_jsonHelper;

    /**
     * Constructor
     *
     * @param Context                  $context               Context
     * @param Registry                 $registry              Registry
     * @param FormFactory              $formFactory           FormFactory
     * @param RuleFactory              $salesRule             RuleFactory
     * @param ObjectConverter          $objectConverter       ObjectConverter
     * @param Store                    $systemStore           Store
     * @param GroupRepositoryInterface $groupRepository       GroupRepositoryInterface
     * @param SearchCriteriaBuilder    $searchCriteriaBuilder SearchCriteriaBuilder
     * @param Course                   $course                Course
     * @param FieldFactory             $fieldFactory          FieldFactory
     * @param JsonHelperData           $jsonHelper            JsonHelperData
     * @param array                    $data                  array
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        RuleFactory $salesRule,
        ObjectConverter $objectConverter,
        Store $systemStore,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Course $course,
        FieldFactory $fieldFactory,
        JsonHelperData $jsonHelper,
        array $data = []
    ) {
        $this->_fieldFactory = $fieldFactory;
        $this->_course = $course;
        $this->_jsonHelper = $jsonHelper;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $salesRule,
            $objectConverter,
            $systemStore,
            $groupRepository,
            $searchCriteriaBuilder,
            $data
        );
    }
    /**
     * Prepare form before rendering HTML
     *
     * @return                                        $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_promo_sales_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $fieldset->addField('rule_id', 'hidden', ['name' => 'rule_id']);
        }

        $fieldset->addField('product_ids', 'hidden', ['name' => 'product_ids']);

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Rule Name'),
                'title' => __('Rule Name'),
                'required' => true,
                'maxlength' => '2047'
            ]
        );

        $fieldset->addField(
            'description',
            'textarea',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
                'style' => 'height: 100px;'
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => ['1' => __('Active'), '0' => __('Inactive')]
            ]
        );

        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }

        if ($this->_storeManager->isSingleStoreMode()) {
            $websiteId = $this->_storeManager->getStore(true)->getWebsiteId();
            $fieldset->addField('website_ids', 'hidden', ['name' => 'website_ids[]', 'value' => $websiteId]);
            $model->setWebsiteIds($websiteId);
        } else {
            $field = $fieldset->addField(
                'website_ids',
                'multiselect',
                [
                    'name' => 'website_ids[]',
                    'label' => __('Websites'),
                    'title' => __('Websites'),
                    'required' => true,
                    'values' => $this->_systemStore->getWebsiteValuesForForm()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
        }

        $groups = $this->groupRepository->getList($this->_searchCriteriaBuilder->create())
            ->getItems();
        $fieldset->addField(
            'customer_group_ids',
            'multiselect',
            [
                'name' => 'customer_group_ids[]',
                'label' => __('Customer Groups'),
                'title' => __('Customer Groups'),
                'required' => true,
                'values' =>  $this->_objectConverter->toOptionArray($groups, 'id', 'code')
            ]
        );

        $couponTypeFiled = $fieldset->addField(
            'coupon_type',
            'select',
            [
                'name' => 'coupon_type',
                'label' => __('Coupon'),
                'required' => true,
                'options' => $this->_salesRule->create()->getCouponTypes()
            ]
        );

        $couponCodeFiled = $fieldset->addField(
            'coupon_code',
            'text',
            ['name' => 'coupon_code', 'label' => __('Coupon Code'), 'required' => true]
        );

        $autoGenerationCheckbox = $fieldset->addField(
            'use_auto_generation',
            'checkbox',
            [
                'name' => 'use_auto_generation',
                'label' => __('Use Auto Generation'),
                'note' => __('If you select and save the rule you will be able to generate multiple coupon codes.'),
                'onclick' => 'handleCouponsTabContentActivity()',
                'checked' => (int)$model->getUseAutoGeneration() > 0 ? 'checked' : ''
            ]
        );

        $autoGenerationCheckbox->setRenderer($this->getLayout()->createBlock('Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Main\Renderer\Checkbox'));

        $usesPerCouponFiled = $fieldset->addField(
            'uses_per_coupon',
            'text',
            ['name' => 'uses_per_coupon', 'label' => __('Uses per Coupon')]
        );

        $fieldset->addField(
            'uses_per_customer',
            'text',
            ['name' => 'uses_per_customer',
                'label' => __('Uses per Customer'),
                'note' => __('Usage limit enforced for logged in customers only.')
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'from_date',
            'date',
            [
                'name' => 'from_date',
                'label' => __('From'),
                'title' => __('From'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat
            ]
        );
        $fieldset->addField(
            'from_time',
            'time',
            [
                'name' => 'from_time',
                'label' => __('From time'),
                'title' => __('From time'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
                'time_format' => $timeFormat
            ]
        );
        $fieldset->addField(
            'to_date',
            'date',
            [
                'name' => 'to_date',
                'label' => __('To'),
                'title' => __('To'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat
            ]
        );
        $fieldset->addField(
            'to_time',
            'time',
            [
                'name' => 'to_time',
                'label' => __('To time'),
                'title' => __('To time'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
                'time_format' => $timeFormat
            ]
        );

        $fieldset->addField('sort_order', 'text', ['name' => 'sort_order', 'label' => __('Priority')]);

        $fieldset->addField(
            'is_rss',
            'select',
            [
                'label' => __('Public In RSS Feed'),
                'title' => __('Public In RSS Feed'),
                'name' => 'is_rss',
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        if (!$model->getId()) {
            //set the default value for is_rss feed to yes for new promotion
            $model->setIsRss(1);
        }

        $isSubscription = $fieldset->addField(
            'subscription',
            'select',
            [
                'name' => 'subscription',
                'label' => __('SPOT/Subscription'),
                'options' => [
                    '-1' => __('Please select'),
                    '0' => __('SPOT only'),
                    '1' => __('Subscription only'),
                    '2' => __('SPOT and subscription')
                ],
                'required' => true,
                'class' => 'validate-select-subscription',
                'onclick' => 'handleSubscriptionDelivery()'
            ]
        );
        if (!$model->getId()) {
            $model->setSubscription(-1);
        }
        $canSelectSubscriptionField = $this->_fieldFactory
            ->create(
                [
                    'fieldData' => [
                        'separator' => ',',
                        'value' => '1,2'
                    ],
                    'fieldPrefix' => ''
                ]
            );

        $applySubscription = $fieldset->addField(
            'apply_subscription',
            'multiselect',
            [
                'name' => 'apply_subscription',
                'label' => __('Apply only to subscription'),
                'values' => $this->_course->getCoursesForForm(),
                'required' => true,
                'onclick' => 'getFrequencyByCourse(this)'
            ]
        );

        $applyFrequency = $fieldset->addField(
            'apply_frequency',
            'multiselect',
            [
                'name' => 'apply_frequency',
                'label' => __('Apply only to frequency'),
                'values' => $this->_course->getFrequencyValuesForForm(),
                'required' => true
            ]
        );

        $fieldset->addField(
            'course_frequency',
            'hidden',
            [
                'name' => 'course_frequency',
                'id' => 'course_frequency'
            ]
        );
        $courseFrequencyData = $this->_jsonHelper->jsonEncode($this->_course->getCourseFrequencyList());
        $model->setCourseFrequency($courseFrequencyData);

        $subscriptionDelivery = $fieldset->addField(
            'subscription_delivery',
            'select',
            [
                'name' => 'subscription_delivery',
                'label' => __('Subscription deliveries'),
                'options' => ['1' => __('Every N delivery'), '2' => __('On N delivery'), '3' => __('All deliveries') , '4' => __('From N delivery')],
                'onclick' => 'handleSubscriptionDelivery()',
            ]
        );
        if (!$model->getId() || !$model->getSubscriptionDelivery()) {
            $model->setSubscriptionDelivery(3);
        }

        $deliveryN = $fieldset->addField(
            'delivery_n',
            'text',
            [
                'name' => 'delivery_n',
                'label' => __('Delivery N'),
                'required' => true,
                'class' => 'validate-number',
                'disabled' => true
            ]
        );

        if ($model->hasFromDate() && $model->hasToDate()) {
            $model->setFromDate(date('Y-m-d', strtotime($model->getFromDate()))) ;
            $model->setToDate(date('Y-m-d', strtotime($model->getToDate())));
        }

        $form->setValues($model->getData());

        $autoGenerationCheckbox->setValue(1);

        if ($model->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                $element->setReadonly(true, true);
            }
        }

        //$form->setUseContainer(true);

        $this->setForm($form);

        // field dependencies
        $this->setChild(
            'form_after',
            $this->getLayout()->createBlock(
                'Magento\SalesRule\Block\Widget\Form\Element\Dependence'
            )->addFieldMap(
                $couponTypeFiled->getHtmlId(),
                $couponTypeFiled->getName()
            )->addFieldMap(
                $couponCodeFiled->getHtmlId(),
                $couponCodeFiled->getName()
            )->addFieldMap(
                $autoGenerationCheckbox->getHtmlId(),
                $autoGenerationCheckbox->getName()
            )->addFieldMap(
                $usesPerCouponFiled->getHtmlId(),
                $usesPerCouponFiled->getName()
            )->addFieldDependence(
                $couponCodeFiled->getName(),
                $couponTypeFiled->getName(),
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
            )->addFieldDependence(
                $autoGenerationCheckbox->getName(),
                $couponTypeFiled->getName(),
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
            )->addFieldDependence(
                $usesPerCouponFiled->getName(),
                $couponTypeFiled->getName(),
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
            )->addFieldMap(
                /*
                 * Add dependence for subscription
                 * */
                $isSubscription->getHtmlId(),
                $isSubscription->getName()
            )->addFieldMap(
                $applySubscription->getHtmlId(),
                $applySubscription->getName()
            )->addFieldMap(
                $applyFrequency->getHtmlId(),
                $applyFrequency->getName()
            )->addFieldMap(
                $subscriptionDelivery->getHtmlId(),
                $subscriptionDelivery->getName()
            )->addFieldMap(
                $deliveryN->getHtmlId(),
                $deliveryN->getName()
            )->addFieldDependence(
                $applySubscription->getName(),
                $isSubscription->getName(),
                $canSelectSubscriptionField
            )->addFieldDependence(
                $applyFrequency->getName(),
                $isSubscription->getName(),
                $canSelectSubscriptionField
            )->addFieldDependence(
                $subscriptionDelivery->getName(),
                $isSubscription->getName(),
                $canSelectSubscriptionField
            )
        );

        $this->_eventManager->dispatch('adminhtml_promo_quote_edit_tab_main_prepare_form', ['form' => $form]);

        return $this;
    }
}
