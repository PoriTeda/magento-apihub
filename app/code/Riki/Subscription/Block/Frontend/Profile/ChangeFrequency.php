<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use Riki\SubscriptionCourse\Model\ResourceModel\Course as CourseResourceModel;

class ChangeFrequency extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $collectionProductCart;

    /**
     * @var \Riki\TimeSlots\Model\TimeSlots
     */
    protected $_timeSlot;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_subCourseHelperData;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $_subHelperData;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseModel;

    /**
     * @var CourseResourceModel
     */
    protected $_courseResourceModel;

    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    /**
     * ChangeFrequency constructor.
     *
     * @param CourseResourceModel $courseResourceModel
     * @param \Riki\Subscription\Helper\Data $subHelperData
     * @param \Riki\SubscriptionCourse\Helper\Data $subCourseHelperData
     * @param \Riki\TimeSlots\Model\TimeSlots $timeSlots
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $collectionProductCart
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param array $data
     */
    public function __construct(
        CourseResourceModel $courseResourceModel,
        \Riki\Subscription\Helper\Data $subHelperData,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelperData,
        \Riki\TimeSlots\Model\TimeSlots $timeSlots,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $collectionProductCart,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        array $data = []
    ) {
        $this->_courseResourceModel = $courseResourceModel;
        $this->_courseModel = $courseModel;
        $this->_subHelperData = $subHelperData;
        $this->_subCourseHelperData = $subCourseHelperData;
        $this->_timeSlot = $timeSlots;
        $this->collectionProductCart = $collectionProductCart;
        $this->_profileData = $profileData;
        $this->_registry = $registry;
        $this->frequencyHelper = $frequencyHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get profile id
     *
     * @return mixed
     */
    public function getProfileId()
    {
        return $this->_registry->registry('subscription-profile-id');
    }

    /**
     * Get profile object model
     *
     * @return \Riki\Subscription\Model\Profile\Profile
     */
    public function getProfileModelObj()
    {
        $profileId = $this->getProfileId();
        return $this->_profileData->load($profileId);
    }

    /**
     * get frequencies option to render select box
     *
     * @param $courseId
     * @return array
     */
    public function getFrequenciesByCourse($courseId)
    {
        $options = $this->_subCourseHelperData->getFrequenciesByCourse($courseId);
        $options[0] = __('Unspecified');
        ksort($options);
        return $options;
    }

    /**
     * Get list frequency
     *
     * @return mixed
     */
    public function getListFrequency()
    {
        /*show message if frequency is invalid or no longer support*/
        $frequencyMessage = '';

        $frequencyOptions = $this->getFrequenciesByCourse(
            $this->getProfileModelObj()->getData("course_id")
        );

        $frequencyUnit = $this->getProfileModelObj()->getData("frequency_unit");
        $frequencyInterval = $this->getProfileModelObj()->getData("frequency_interval");

        $value = $this->_subHelperData->getFrequencyIdByUnitAndInterval($frequencyUnit, $frequencyInterval);

        if (empty($value)) {
            $frequencyMessage = '<span class="frequency-message">'.__('Frequency is invalid').'</span>';
        }

        if (!empty($value) && !isset($frequencyOptions[$value])) {
            $currentFrequency = $this->frequencyHelper->formatFrequency(
                $frequencyInterval,
                $frequencyUnit
            );

            $frequencyMessage = '<span class="frequency-message">'.$currentFrequency.' '.__('(No longer support)').'</span>';
        }

        if ($this->getCourseById($this->getProfileModelObj()->getData('course_id'))) {
            $isAllow = $this->getCourseById(
                $this->getProfileModelObj()->getData('course_id')
            )->isAllowChangeNextDeliveryDate();
        } else {
            $isAllow = true;
        }

        $strAttr = '';
        if (!$isAllow) {
            $strAttr = "disabled";
        }

        /*html for frequency select box*/
        $frequencySelectBox = $this->getFrequencySelectBoxHtml(
            $frequencyOptions,
            'frequency_id',
            'frequency_id',
            $value,
            'required-entry input-new validate-frequency',
            $strAttr
        );

        return $frequencyMessage.$frequencySelectBox;
    }

    /**
     * get frequency select box html
     *
     * @param $options
     * @param $name
     * @param $id
     * @param bool $value
     * @param string $class
     * @param string $strAttr
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFrequencySelectBoxHtml($options, $name, $id, $value = false, $class = '', $strAttr = '')
    {
        $arrData = ['id' => $id, 'class' => 'select global-scope ' . $class];

        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            $arrData
        )->setName(
            $name
        )->setValue(
            $value
        )->setOptions(
            $options
        );

        $select->setExtraParams($strAttr);

        return '<div class="select-wrapper">'.$select->getHtml().'</div>';
    }

    /**
     * Get course by id
     *
     * @param $courseId
     *
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    public function getCourseById($courseId)
    {
        return $this->_courseModel->load($courseId);
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Change Frequency'));
        return parent::_prepareLayout();
    }

    public function getUrlProfileList()
    {
        return $this->getUrl('subscriptions/profile/index');
    }
}
