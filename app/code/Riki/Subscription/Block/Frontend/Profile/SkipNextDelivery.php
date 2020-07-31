<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use Magento\Framework\Exception\NoSuchEntityException;

class SkipNextDelivery extends \Magento\Framework\View\Element\Template
{

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    /* @var \Riki\Subscription\Model\ProductCart\ProductCartFactory */
    protected $collectionProductCart;

    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $_timeSlot;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $_profileRepository;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    public function __construct(
        \Riki\TimeSlots\Model\TimeSlots $timeSlots,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $collectionProductCart,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        array $data = []
    ){
        $this->_timeSlot = $timeSlots;
        $this->collectionProductCart = $collectionProductCart;
        $this->_profileData = $profileData;
        $this->_registry = $registry;
        $this->_profileRepository = $profileRepository;
        $this->_courseFactory = $courseFactory;
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

    public function getSlotName()
    {
        $profileId = $this->getProfileId();
        $collection = $this->collectionProductCart->create()->getCollection();
        $collection->addFieldToSelect('*');
        $productProfileCollection = $collection->addFieldToFilter('profile_id', array($profileId));
        if ($productProfileCollection->getSize() > 0) {
            $timeSlotId = $productProfileCollection->getFirstItem()->getData('delivery_time_slot');
            $timeSlotModel = $this->getSlotObject($timeSlotId);
            if ($timeSlotModel != null) {
                return $timeSlotModel->getData('slot_name');
            } else {
                return '';
            }
        }
        return '';
    }

    public function getSlotObject($slotId)
    {
        $slotModel = $this->_timeSlot->load($slotId);
        if ($slotModel && $slotModel->getId())
        {
            return $slotModel;
        }
        return null;
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Skip next delivery'));
        return parent::_prepareLayout();
    }

    public function checkAllowSkipNextDelivery($profileId)
    {
        try {
            $profileModel = $this->_profileRepository->get($profileId);

            $courseId = $profileModel->getCourseId();
            $courseModel = $this->_courseFactory->create()->load($courseId);
            $allowSkipNextDelivery = $courseModel->getData('allow_skip_next_delivery');
            if ($allowSkipNextDelivery) {
                return true;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }

    public function getNextDeliveryDateAfterSkip($deliveryDate,$frequencyUnit,$frequencyInterval){
        $time  = strtotime($deliveryDate);
        $timestamp = strtotime($frequencyInterval . " " . $frequencyUnit, $time);

        $objDate  = new \DateTime();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * Is day of week and unit month and not stock point
     * If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
     * AND interval_unit="month"
     * AND not Stock Point
     *
     * @return boolean
     */
    public function isDayOfWeekAndUnitMonthAndNotStockPoint()
    {
        $profileModel = $this->getProfileModelObj();

        $courseId = $profileModel->getCourseId();
        $courseModel = $this->_courseFactory->create()->load($courseId);

        if (empty($courseModel) || empty($courseModel->getId())) {
            return false;
        }

        if ($courseModel->getData('next_delivery_date_calculation_option')
            == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
            && $profileModel->getData('frequency_unit') == 'month'
            && !$profileModel->getData('stock_point_profile_bucket_id')
        ) {
            return true;
        }

        return false;
    }
}