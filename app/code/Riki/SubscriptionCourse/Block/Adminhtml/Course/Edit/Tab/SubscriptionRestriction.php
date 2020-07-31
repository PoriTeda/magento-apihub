<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

use \Riki\SubscriptionCourse\Model\Course\Source\QtyRestrictionOptions;

class SubscriptionRestriction extends \Magento\Backend\Block\Widget\Form\Generic
    implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'subscription_restriction.phtml';
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $course;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course\Source\AmountRestrictionOptions
     */
    protected $orderAmountRestrictionOptions;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course\Source\QtyRestrictionOptions
     */
    protected $qtyRestrictionOptions;

    /**
     * OrderAmountRestriction constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param \Riki\SubscriptionCourse\Model\Course\Source\AmountRestrictionOptions $amountOptions
     * @param \Riki\SubscriptionCourse\Model\Course\Source\QtyRestrictionOptions $qtyRestrictionOptions
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Riki\SubscriptionCourse\Model\Course $course,
        \Riki\SubscriptionCourse\Model\Course\Source\AmountRestrictionOptions $amountOptions,
        \Riki\SubscriptionCourse\Model\Course\Source\QtyRestrictionOptions $qtyRestrictionOptions,
        array $data = []
    ) {
        $this->course = $course;
        $this->orderAmountRestrictionOptions = $amountOptions;
        $this->qtyRestrictionOptions = $qtyRestrictionOptions;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'minimum_amount_add_options_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Create New Option'), 'class' => 'add', 'id' => 'minimum_amount_add_new_defined_option']
        );
        $this->addChild(
            'minimum_amount_options_box',
            \Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\AmountRestriction\Option::class
        );

        $this->addChild(
            'maximum_qty_add_options_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Create New Option'), 'class' => 'add', 'id' => 'maximum_qty_add_new_defined_option']
        );
        $this->addChild(
            'maximum_qty_options_box',
            \Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\QtyRestriction\Option::class
        );
        return parent::_prepareLayout();
    }

    /**
     * Prepare form
     * @return $this
     */
    protected function _prepareForm()
    {
        /* @var $model \Riki\SubscriptionCourse\Model\Course */
        $model = $this->_coreRegistry->registry('subscription_course');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('cou_');
        $fieldset = $form->addFieldset(
            'order_amount_restriction_fieldset',
            ['legend' => __('Subscription Restriction')]
        );
        // field "Order total minimum amount option"
        $fieldset->addField(
            'order_total_amount_option',
            'select',
            [
                'name' => 'order_total_amount_option',
                'label' => __('Order total minimum amount option'),
                'title' => __('Order total minimum amount option'),
                'values' => $this->orderAmountRestrictionOptions->toOptionArray()
            ]
        );
        // field "Order total minimum amount threshold"
        $fieldset->addField(
            'oar_minimum_amount_threshold',
            'text',
            [
                'name' => 'oar_minimum_amount_threshold',
                'label' => __('Order total minimum amount threshold'),
                'title' => __('Order total minimum amount threshold'),
                'class' => 'validate-number validate-greater-than-zero'
            ]
        );
        // field "Order total Maximum amount threshold"
        $fieldset->addField(
            'oar_maximum_amount_threshold',
            'text',
            [
                'name' => 'oar_maximum_amount_threshold',
                'label' => __('Order total Maximum amount threshold'),
                'title' => __('Order total Maximum amount threshold'),
                'class' => 'validate-number validate-greater-than-zero validate-greater-than-min-amount',
            ]
        );

        // field "Maximum Qty Restriction Option"
        $fieldset->addField(
            'maximum_qty_restriction_option',
            'select',
            [
                'name' => 'maximum_qty_restriction_option',
                'label' => __('Maximum Qty Restriction Option'),
                'title' => __('Maximum Qty Restriction Option'),
                'values' => $this->qtyRestrictionOptions->toOptionArray()
            ]
        );

        // field "Maximum Qty Restriction"
        $fieldset->addField(
            'oqr_maximum_qty_restriction',
            'text',
            [
                'name' => 'oqr_maximum_qty_restriction',
                'label' => __('Maximum Qty Restriction'),
                'title' => __('Maximum Qty Restriction'),
                'class' => 'validate-number validate-greater-than-zero'
            ]
        );

        $model->setData('oar_minimum_amount_threshold', $this->getSingleMinAmount());
        $model->setData('oar_maximum_amount_threshold', $this->getSingleMaxAmount());
        $model->setData('oqr_maximum_qty_restriction', $this->getSingleMaximumQtyRestriction());
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
    /**
     * @param $alias
     * @return string
     */
    public function getOptionsBoxHtml($alias)
    {
        return $this->getChildHtml($alias);
    }
    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Subscription Restriction');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Subscription Restriction');
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
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * @return mixed
     */
    public function getCurrentCourse()
    {
        return $this->_coreRegistry->registry('subscription_course');
    }

    /**
     * get minimum total amount
     */
    public function getSingleMinAmount()
    {
        $subscriptionCourse = $this->getCurrentCourse();
        if ($subscriptionCourse->getId()) {
            $condition = $subscriptionCourse->getData('oar_condition_serialized');
            if ($condition) {
                $serializeData = json_decode($condition, true);
                if ($serializeData['minimum']['option']!=2) {
                    return $serializeData['minimum']['amount'];
                }
            }
        }
    }

    /**
     * get maximum total amount
     */
    public function getSingleMaxAmount()
    {
        $subscriptionCourse = $this->getCurrentCourse();
        if ($subscriptionCourse->getId()) {
            $condition = $subscriptionCourse->getData('oar_condition_serialized');
            if ($condition) {
                $serializeData = json_decode($condition, true);
                if (isset($serializeData['maximum']['amount'])) {
                    return $serializeData['maximum']['amount'];
                }
            }
        }
    }

    /**
     * Get maximum qty restriction
     */
    public function getSingleMaximumQtyRestriction()
    {
        $subscriptionCourse = $this->getCurrentCourse();
        if ($subscriptionCourse->getId()) {
            $condition = $subscriptionCourse->getData('maximum_qty_restriction');
            if ($condition) {
                $serializeData = json_decode($condition, true);
                if ($serializeData['maximum']['option'] != QtyRestrictionOptions::OPTION_VALUE_CUSTOM_ORDER) {
                    return $serializeData['maximum']['qty'];
                }
            }
        }
    }

    /**
     * Is hanpukai subscription course
     *
     * @return boolean
     */
    public function isHanpukaiSubscriptionCourse()
    {
        $courseModel = $this->_coreRegistry->registry('subscription_course');

        if ($courseModel->getData('subscription_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            return true;
        }

        return false;
    }
}
